<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReportGeneratedMail;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $tries = 2;
    protected int $timeout = 300; // 5 minutes

    public function __construct(
        protected User $user,
        protected string $reportType,
        protected array $filters = [],
        protected string $format = 'excel',
        protected bool $sendEmail = true
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $reportService = app(ReportService::class);

            // Generate report based on type
            $reportData = match ($this->reportType) {
                'requisitions' => $reportService->getRequisitions($this->filters),
                'procurement' => $reportService->getProcurementReports($this->filters),
                'suppliers' => $reportService->getSupplierReport($this->filters),
                'supplier_performance' => $reportService->getSupplierPerformanceReport($this->filters),
                'inventory' => $reportService->getInventoryReport($this->filters),
                'inventory_valuation' => $reportService->getInventoryValuationReport($this->filters),
                'inventory_movements' => $reportService->getInventoryMovementsReport($this->filters),
                'financial' => $reportService->getFinancialReport($this->filters),
                'budget' => $reportService->getBudgetReport($this->filters),
                'cash_flow' => $reportService->getCashFlowReport($this->filters),
                'expenditure' => $reportService->getExpenditureReport($this->filters),
                'wht' => $reportService->getWHTReport($this->filters),
                default => throw new \Exception("Unknown report type: {$this->reportType}")
            };

            // Export to requested format
            $filename = $this->generateFilename();
            $filepath = $this->exportReport($reportData, $filename);

            // Store file reference in database
            $this->storeReportReference($filepath, $filename);

            // Send email notification if requested
            if ($this->sendEmail) {
                Mail::to($this->user->email)->send(
                    new ReportGeneratedMail($this->user, $this->reportType, $filepath)
                );
            }

            // Audit log
            app(\App\Core\Audit\AuditService::class)->log(
                'REPORT_GENERATED',
                'Report',
                $this->user->id,
                null,
                null,
                "Report '{$this->reportType}' generated in {$this->format} format",
                [
                    'report_type' => $this->reportType,
                    'format' => $this->format,
                    'filename' => $filename,
                    'filters' => $this->filters,
                ]
            );
        } catch (\Exception $e) {
            app(\App\Core\Audit\AuditService::class)->log(
                'REPORT_FAILED',
                'Report',
                $this->user->id,
                null,
                null,
                "Failed to generate report '{$this->reportType}': {$e->getMessage()}",
                [
                    'error' => $e->getMessage(),
                    'report_type' => $this->reportType,
                ]
            );

            throw $e;
        }
    }

    /**
     * Export report to specified format
     */
    protected function exportReport(array $data, string $filename): string
    {
        return match ($this->format) {
            'excel' => $this->exportToExcel($data, $filename),
            'pdf' => $this->exportToPDF($data, $filename),
            'csv' => $this->exportToCSV($data, $filename),
            default => throw new \Exception("Unsupported export format: {$this->format}")
        };
    }

    /**
     * Export to Excel format
     */
    protected function exportToExcel(array $data, string $filename): string
    {
        // Use Maatwebsite Excel library
        $export = new \App\Exports\DynamicReportExport($data, $this->reportType);
        $path = "reports/{$filename}.xlsx";

        \Maatwebsite\Excel\Facades\Excel::store($export, $path, 'local');

        return $path;
    }

    /**
     * Export to PDF format
     */
    protected function exportToPDF(array $data, string $filename): string
    {
        $html = view("reports.templates.{$this->reportType}", ['data' => $data])->render();
        $pdf = \PDF::loadHTML($html);
        $path = "reports/{$filename}.pdf";

        $pdf->save(Storage::path($path));

        return $path;
    }

    /**
     * Export to CSV format
     */
    protected function exportToCSV(array $data, string $filename): string
    {
        $path = "reports/{$filename}.csv";
        $file = fopen(Storage::path($path), 'w');

        // Write CSV data
        foreach ($data as $row) {
            fputcsv($file, $row);
        }

        fclose($file);

        return $path;
    }

    /**
     * Generate unique filename with timestamp
     */
    protected function generateFilename(): string
    {
        return "{$this->reportType}_" . date('Y-m-d_His');
    }

    /**
     * Store report reference in database
     */
    protected function storeReportReference(string $filepath, string $filename): void
    {
        \DB::table('generated_reports')->insert([
            'user_id' => $this->user->id,
            'report_type' => $this->reportType,
            'filename' => $filename,
            'filepath' => $filepath,
            'format' => $this->format,
            'filters' => json_encode($this->filters),
            'generated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'reports',
            'report:' . $this->reportType,
            'format:' . $this->format,
            'user:' . $this->user->id,
        ];
    }
}
