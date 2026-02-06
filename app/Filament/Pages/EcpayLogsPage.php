<?php

namespace App\Filament\Pages;

use App\Services\EcpayLogReader;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class EcpayLogsPage extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = '系統日誌';

    protected static ?string $title = '系統日誌';

    public static function canAccess(): bool
    {
        return auth()->user()?->is_admin ?? false;
    }

    protected static ?string $navigationGroup = '系統管理';

    protected static ?int $navigationSort = 102;

    protected static string $view = 'filament.pages.ecpay-logs-page';

    // 不綁定租戶
    protected static bool $isScopedToTenant = false;

    #[Url]
    public ?string $startDate = null;

    #[Url]
    public ?string $endDate = null;

    #[Url]
    public ?string $logType = null;

    public int $perPage = 20;

    public function mount(): void
    {
        // 預設顯示今天的日誌
        $this->startDate = $this->startDate ?? now()->format('Y-m-d');
        $this->endDate = $this->endDate ?? now()->format('Y-m-d');
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function updatedLogType(): void
    {
        $this->resetPage();
    }

    public function getViewData(): array
    {
        $reader = app(EcpayLogReader::class);
        $allLogs = $reader->getLogs($this->startDate, $this->endDate, $this->logType);

        // 手動分頁
        $page = $this->getPage();
        $total = $allLogs->count();
        $logs = $allLogs->slice(($page - 1) * $this->perPage, $this->perPage)->values();

        return [
            'logs' => $logs,
            'totalCount' => $total,
            'currentPage' => $page,
            'lastPage' => (int) ceil($total / $this->perPage),
            'perPage' => $this->perPage,
        ];
    }

    public function previousPage(): void
    {
        $this->setPage(max(1, $this->getPage() - 1));
    }

    public function nextPage(): void
    {
        $data = $this->getViewData();
        $this->setPage(min($data['lastPage'], $this->getPage() + 1));
    }

    public function goToPage(int $page): void
    {
        $this->setPage($page);
    }
}
