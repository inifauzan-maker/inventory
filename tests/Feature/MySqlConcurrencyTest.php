<?php

namespace Tests\Feature;

use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class MySqlConcurrencyTest extends TestCase
{
    private ?string $probeDatabase = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('SIIP_MYSQL_CONCURRENCY') !== '1') {
            $this->markTestSkipped('Aktifkan SIIP_MYSQL_CONCURRENCY=1 untuk database MySQL uji terisolasi.');
        }
        $databaseName = 'siip_concurrency_'.bin2hex(random_bytes(8));
        $configuration = config('database.connections.mysql');
        $configuration['url'] = null;
        $configuration['database'] = null;
        config(['database.connections.probe_admin' => $configuration]);
        DB::connection('probe_admin')->statement('CREATE DATABASE '.$databaseName.' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->probeDatabase = $databaseName;
        $configuration['database'] = $this->probeDatabase;
        config(['database.connections.probe' => $configuration]);
        DB::setDefaultConnection('probe');
        Artisan::call('migrate', ['--database' => 'probe', '--force' => true, '--no-interaction' => true]);
    }

    protected function tearDown(): void
    {
        if ($this->probeDatabase !== null && preg_match('/^siip_concurrency_[a-f0-9]{16}$/', $this->probeDatabase)) {
            DB::disconnect('probe');
            DB::connection('probe_admin')->statement('DROP DATABASE '.$this->probeDatabase);
        }
        parent::tearDown();
    }

    public function test_simultaneous_number_allocation_returns_distinct_sequential_numbers(): void
    {
        $results = $this->runWorkers('number', [], 8);
        $this->assertCount(8, array_unique($results));
        sort($results);
        $year = now()->format('Y');
        $this->assertSame(array_map(fn (int $n): string => 'INV-'.$year.'-'.str_pad((string) $n, 6, '0', STR_PAD_LEFT), range(1, 8)), $results);
        $this->assertSame(8, (int) DB::table('inventory_number_sequences')->value('last_number'));
    }

    public function test_simultaneous_receipts_cannot_exceed_remaining_order_quantity(): void
    {
        $order = PurchaseOrder::create(['order_number' => 'UJI-CONCURRENT', 'vendor_name' => 'SIMULASI', 'order_date' => '2026-10-06', 'status' => 'approved']);
        $item = PurchaseOrderItem::create(['purchase_order_id' => $order->id, 'item_name' => 'SIMULASI', 'quantity' => 1, 'unit_price' => '1000.00', 'received_quantity' => 0]);

        $results = $this->runWorkers('receipt', ['order_id' => $order->id, 'item_id' => $item->id], 2);
        sort($results);

        $this->assertSame(['received', 'rejected'], $results);
        $this->assertSame(1, (int) $item->fresh()->received_quantity);
        $this->assertSame(1, DB::table('goods_receipts')->count());
        $this->assertSame(1, (int) DB::table('goods_receipt_items')->sum('quantity_received'));
        $this->assertSame(0, DB::table('assets')->count());
    }

    public function test_simultaneous_purchase_orders_cannot_exceed_approved_request_quantity(): void
    {
        $proposal = ProcurementRequest::factory()->create(['status' => 'approved']);
        $item = ProcurementRequestItem::factory()->create(['procurement_request_id' => $proposal->id, 'quantity' => 1]);

        $results = $this->runWorkers('purchase_order', ['proposal_id' => $proposal->id, 'item_id' => $item->id], 2);
        sort($results);

        $this->assertSame(['ordered', 'rejected'], $results);
        $this->assertSame(1, PurchaseOrder::where('procurement_request_id', $proposal->id)->count());
        $this->assertSame(1, (int) PurchaseOrderItem::where('procurement_request_item_id', $item->id)->sum('quantity'));
        $this->assertSame('draft', PurchaseOrder::first()->status);
    }

    private function runWorkers(string $operation, array $data, int $count): array
    {
        $code = <<<'PHP'
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$input = json_decode(stream_get_contents(STDIN), true);
$app["config"]->set("database.connections.probe", $input["connection"]);
Illuminate\Support\Facades\DB::setDefaultConnection("probe");
while (microtime(true) < $input["start"]) { usleep(1000); }
if ($input["operation"] === "number") {
    echo (new App\InventoryNumberService)->nextAssetNumber();
} elseif ($input["operation"] === "purchase_order") {
    try {
        (new App\PurchaseOrderService)->create([
            "procurement_request_id" => $input["data"]["proposal_id"],
            "order_number" => "PO-PARALLEL-".$input["index"],
            "vendor_name" => "SIMULASI",
            "order_date" => "2026-10-07",
            "items" => [["procurement_request_item_id" => $input["data"]["item_id"], "item_name" => "SIMULASI", "quantity" => 1, "unit_price" => "1000.00"]],
        ]);
        echo "ordered";
    } catch (Illuminate\Validation\ValidationException $exception) {
        echo "rejected";
    }
} else {
    try {
        (new App\ReceiptWorkflowService)->receive([
            "purchase_order_id" => $input["data"]["order_id"],
            "receipt_number" => "RCV-UJI-".$input["index"],
            "received_at" => "2026-10-06",
            "items" => [["purchase_order_item_id" => $input["data"]["item_id"], "quantity_received" => 1]],
        ]);
        echo "received";
    } catch (Illuminate\Validation\ValidationException $exception) {
        echo "rejected";
    }
}
PHP;
        $processes = [];
        $start = microtime(true) + 3;
        for ($index = 0; $index < $count; $index++) {
            $process = new Process([PHP_BINARY, '-r', $code], base_path(), null, null, 20);
            $process->setInput(json_encode(['connection' => config('database.connections.probe'), 'start' => $start, 'operation' => $operation, 'data' => $data, 'index' => $index]));
            $process->start();
            $processes[] = $process;
        }
        $results = [];
        foreach ($processes as $process) {
            $process->wait();
            $this->assertTrue($process->isSuccessful(), 'Worker konkurensi gagal; periksa runtime PHP dan database pengujian.');
            $results[] = trim($process->getOutput());
        }

        return $results;
    }
}
