<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

class LotteryAnalysisExport implements WithMultipleSheets
{
    private array $result;

    private array $meta;

    public function __construct(array $result, array $meta = [])
    {
        $this->result = $result;
        $this->meta = $meta;
    }

    public function sheets(): array
    {
        $data = $this->buildData();

        return [
            new LotteryAnalysisSummarySheet($data['summary_rows']),
            new LotteryAnalysisPrizesSheet($data['prize_rows']),
            new LotteryAnalysisDepartmentsSheet($data['department_rows']),
            new LotteryAnalysisComparisonSheet($data['comparison_rows']),
            new LotteryAnalysisChartsSheet($data['chart_data']),
            new LotteryAnalysisPivotSheet($data['pivot_rows']),
        ];
    }

    private function buildData(): array
    {
        $prizes = $this->result['prizes'] ?? [];
        $departments = $this->result['departments'] ?? [];
        $summary = $this->result['summary'] ?? [];

        $iterations = (int) ($this->meta['iterations'] ?? $this->result['iterations'] ?? 0);
        $generatedAt = $this->meta['generated_at'] ?? $this->result['generated_at'] ?? '';
        $eventName = $this->meta['event_name'] ?? '';
        $brandCode = $this->meta['brand_code'] ?? '';
        $analysisId = $this->meta['analysis_id'] ?? '';

        $totalEligible = (int) ($summary['eligible_exposures'] ?? 0);
        $totalWins = (int) ($summary['total_wins'] ?? 0);
        $overallWinRate = $totalEligible > 0 ? $totalWins / $totalEligible : 0.0;

        $summaryRows = [[
            $eventName,
            $brandCode,
            $analysisId,
            $iterations,
            $generatedAt,
            count($prizes),
            count($departments),
            $totalEligible,
            $totalWins,
            $this->toPercent($overallWinRate),
        ]];

        $prizeRows = [];
        $chartData = [
            'prize_names' => [],
            'prize_win_rates' => [],
            'prize_relative_rates' => [],
            'department_names' => [],
            'department_eligible_pct' => [],
            'department_win_pct' => [],
            'department_delta_pct' => [],
        ];
        foreach ($prizes as $prize) {
            $winRate = (float) ($prize['win_rate'] ?? 0);
            $relativeWinRate = $overallWinRate > 0 ? $winRate / $overallWinRate : null;

            $chartData['prize_names'][] = (string) ($prize['name'] ?? '');
            $chartData['prize_win_rates'][] = $this->toPercent($winRate);
            $chartData['prize_relative_rates'][] = $this->normalizeNumber($relativeWinRate);

            $prizeRows[] = [
                $prize['id'] ?? '',
                $prize['name'] ?? '',
                $this->formatDrawMode($prize['draw_mode'] ?? null),
                $this->formatAllowRepeat($prize['allow_repeat_within_prize'] ?? null),
                $prize['winners_count'] ?? '',
                $this->roundNumber($prize['eligible_avg'] ?? 0, 2),
                $prize['eligible_exposures'] ?? 0,
                $prize['total_wins'] ?? 0,
                $this->toPercent($winRate),
                $this->roundNumber($relativeWinRate, 4),
            ];
        }

        $departmentRows = [];
        foreach ($departments as $dept) {
            $eligibleExposure = (int) ($dept['eligible_exposures'] ?? 0);
            $winCount = (int) ($dept['win_count'] ?? 0);
            $eligiblePct = (float) ($dept['eligible_pct'] ?? 0);
            $winPct = (float) ($dept['win_pct'] ?? 0);
            $deltaPct = (float) ($dept['delta_pct'] ?? 0);
            $deptWinRate = $eligibleExposure > 0 ? $winCount / $eligibleExposure : 0.0;
            $relativeWinRate = $overallWinRate > 0 ? $deptWinRate / $overallWinRate : null;

            $chartData['department_names'][] = (string) ($dept['department'] ?? '');
            $chartData['department_eligible_pct'][] = $this->toPercent($eligiblePct);
            $chartData['department_win_pct'][] = $this->toPercent($winPct);
            $chartData['department_delta_pct'][] = $this->toPercent($deltaPct);

            $departmentRows[] = [
                $dept['department'] ?? '',
                $eligibleExposure,
                $this->toPercent($eligiblePct),
                $winCount,
                $this->toPercent($winPct),
                $this->toPercent($deltaPct),
                $this->toPercent($deptWinRate),
                $this->roundNumber($relativeWinRate, 4),
            ];
        }

        $comparisonRows = [];
        foreach ($prizes as $prize) {
            $winRate = (float) ($prize['win_rate'] ?? 0);
            $relativeWinRate = $overallWinRate > 0 ? $winRate / $overallWinRate : null;

            $comparisonRows[] = [
                '獎項',
                $prize['name'] ?? '',
                $prize['eligible_exposures'] ?? 0,
                $prize['total_wins'] ?? 0,
                $this->toPercent($winRate),
                $this->roundNumber($relativeWinRate, 4),
                '',
                '',
                '',
            ];
        }

        foreach ($departments as $dept) {
            $eligibleExposure = (int) ($dept['eligible_exposures'] ?? 0);
            $winCount = (int) ($dept['win_count'] ?? 0);
            $eligiblePct = (float) ($dept['eligible_pct'] ?? 0);
            $winPct = (float) ($dept['win_pct'] ?? 0);
            $deltaPct = (float) ($dept['delta_pct'] ?? 0);
            $deptWinRate = $eligibleExposure > 0 ? $winCount / $eligibleExposure : 0.0;
            $relativeWinRate = $overallWinRate > 0 ? $deptWinRate / $overallWinRate : null;

            $comparisonRows[] = [
                '部門',
                $dept['department'] ?? '',
                $eligibleExposure,
                $winCount,
                $this->toPercent($deptWinRate),
                $this->roundNumber($relativeWinRate, 4),
                $this->toPercent($eligiblePct),
                $this->toPercent($winPct),
                $this->toPercent($deltaPct),
            ];
        }

        $pivotRows = [];
        foreach ($prizes as $prize) {
            $winRate = (float) ($prize['win_rate'] ?? 0);
            $relativeWinRate = $overallWinRate > 0 ? $winRate / $overallWinRate : null;

            $pivotRows = array_merge($pivotRows, $this->buildPivotRows(
                '獎項',
                $prize['name'] ?? '',
                [
                    ['中獎率(%)', $this->toPercent($winRate), '%'],
                    ['相對中獎率(倍)', $this->roundNumber($relativeWinRate, 4), '倍'],
                    ['平均可抽人數', $this->roundNumber($prize['eligible_avg'] ?? 0, 2), '人'],
                    ['可抽人次累計', $prize['eligible_exposures'] ?? 0, '次'],
                    ['中獎次數', $prize['total_wins'] ?? 0, '次'],
                    ['中獎人數', $prize['winners_count'] ?? 0, '人'],
                ],
                $iterations,
                $generatedAt,
                $eventName,
                $brandCode,
                $analysisId
            ));
        }

        foreach ($departments as $dept) {
            $eligibleExposure = (int) ($dept['eligible_exposures'] ?? 0);
            $winCount = (int) ($dept['win_count'] ?? 0);
            $eligiblePct = (float) ($dept['eligible_pct'] ?? 0);
            $winPct = (float) ($dept['win_pct'] ?? 0);
            $deltaPct = (float) ($dept['delta_pct'] ?? 0);
            $deptWinRate = $eligibleExposure > 0 ? $winCount / $eligibleExposure : 0.0;
            $relativeWinRate = $overallWinRate > 0 ? $deptWinRate / $overallWinRate : null;

            $pivotRows = array_merge($pivotRows, $this->buildPivotRows(
                '部門',
                $dept['department'] ?? '',
                [
                    ['部門中獎率(%)', $this->toPercent($deptWinRate), '%'],
                    ['相對中獎率(倍)', $this->roundNumber($relativeWinRate, 4), '倍'],
                    ['可抽占比(%)', $this->toPercent($eligiblePct), '%'],
                    ['中獎占比(%)', $this->toPercent($winPct), '%'],
                    ['占比差異(%)', $this->toPercent($deltaPct), '%'],
                    ['部門可抽人次', $eligibleExposure, '次'],
                    ['部門中獎次數', $winCount, '次'],
                ],
                $iterations,
                $generatedAt,
                $eventName,
                $brandCode,
                $analysisId
            ));
        }

        return [
            'summary_rows' => $summaryRows,
            'prize_rows' => $prizeRows,
            'department_rows' => $departmentRows,
            'comparison_rows' => $comparisonRows,
            'pivot_rows' => $pivotRows,
            'chart_data' => $chartData,
        ];
    }

    private function buildPivotRows(
        string $category,
        string $name,
        array $metrics,
        int $iterations,
        string $generatedAt,
        string $eventName,
        string $brandCode,
        string $analysisId
    ): array {
        $rows = [];
        foreach ($metrics as $metric) {
            $rows[] = [
                $category,
                $name,
                $metric[0] ?? '',
                $metric[1] ?? null,
                $metric[2] ?? '',
                $iterations,
                $generatedAt,
                $eventName,
                $brandCode,
                $analysisId,
            ];
        }

        return $rows;
    }

    private function formatDrawMode(?string $value): string
    {
        return match ($value) {
            'one_by_one' => '逐一抽出',
            'all_at_once' => '一次全抽',
            default => (string) $value,
        };
    }

    private function formatAllowRepeat($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return (bool) $value ? '是' : '否';
    }

    private function toPercent(float $value, int $precision = 2): float
    {
        return round($value * 100, $precision);
    }

    private function roundNumber($value, int $precision = 2)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, $precision);
    }

    private function normalizeNumber($value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }
}

final class LotteryAnalysisSummarySheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function title(): string
    {
        return '總覽';
    }

    public function headings(): array
    {
        return [
            '活動名稱',
            'Brand Code',
            '分析ID',
            '模擬次數',
            '生成時間',
            '獎項數',
            '部門數',
            '總可抽人次',
            '總中獎次數',
            '總中獎率(%)',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }
}

final class LotteryAnalysisPrizesSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function title(): string
    {
        return '獎項統計';
    }

    public function headings(): array
    {
        return [
            '獎項ID',
            '獎項名稱',
            '抽獎模式',
            '同一獎項可重複中獎',
            '中獎人數',
            '平均可抽人數',
            '可抽人次累計',
            '中獎次數',
            '中獎率(%)',
            '相對中獎率(倍)',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }
}

final class LotteryAnalysisDepartmentsSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function title(): string
    {
        return '部門比較';
    }

    public function headings(): array
    {
        return [
            '部門',
            '部門可抽人次',
            '部門可抽占比(%)',
            '部門中獎次數',
            '部門中獎占比(%)',
            '占比差異(%)',
            '部門中獎率(%)',
            '相對中獎率(倍)',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }
}

final class LotteryAnalysisComparisonSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function title(): string
    {
        return '機率比對';
    }

    public function headings(): array
    {
        return [
            '類別',
            '名稱',
            '可抽人次',
            '中獎次數',
            '中獎率(%)',
            '相對中獎率(倍)',
            '可抽占比(%)',
            '中獎占比(%)',
            '占比差異(%)',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }
}

final class LotteryAnalysisChartsSheet implements FromArray, WithCharts, WithTitle
{
    private array $prizeNames;

    private array $prizeWinRates;

    private array $prizeRelativeRates;

    private array $departmentNames;

    private array $departmentEligiblePct;

    private array $departmentWinPct;

    private array $departmentDeltaPct;

    private int $prizeCount;

    private int $departmentCount;

    public function __construct(array $chartData)
    {
        $this->prizeNames = $chartData['prize_names'] ?? [];
        $this->prizeWinRates = $chartData['prize_win_rates'] ?? [];
        $this->prizeRelativeRates = $chartData['prize_relative_rates'] ?? [];
        $this->departmentNames = $chartData['department_names'] ?? [];
        $this->departmentEligiblePct = $chartData['department_eligible_pct'] ?? [];
        $this->departmentWinPct = $chartData['department_win_pct'] ?? [];
        $this->departmentDeltaPct = $chartData['department_delta_pct'] ?? [];
        $this->prizeCount = count($this->prizeNames);
        $this->departmentCount = count($this->departmentNames);
    }

    public function title(): string
    {
        return '圖表';
    }

    public function array(): array
    {
        return [];
    }

    public function charts(): array
    {
        $charts = [];

        if ($this->prizeCount > 0) {
            $endRow = $this->prizeCount + 1;
            $charts[] = $this->buildPrizeWinRateChart($endRow, 'A1', 'H14');
            $charts[] = $this->buildPrizeRelativeChart($endRow, 'J1', 'Q14');
        }

        if ($this->departmentCount > 0) {
            $endRow = $this->departmentCount + 1;
            $charts[] = $this->buildDepartmentShareChart($endRow, 'A16', 'H31');
            $charts[] = $this->buildDepartmentDeltaChart($endRow, 'J16', 'Q31');
        }

        return $charts;
    }

    private function buildPrizeWinRateChart(int $endRow, string $topLeft, string $bottomRight): Chart
    {
        $labels = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $this->cellRef('獎項統計', 'I', 1),
                null,
                1
            ),
        ];
        $categories = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $this->rangeRef('獎項統計', 'B', 2, $endRow),
                null,
                $this->prizeCount,
                $this->prizeNames
            ),
        ];
        $values = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                $this->rangeRef('獎項統計', 'I', 2, $endRow),
                null,
                $this->prizeCount,
                $this->prizeWinRates
            ),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($values) - 1),
            $labels,
            $categories,
            $values
        );
        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $title = new Title('獎項中獎率(%)');

        $chart = new Chart('prize_win_rate', $title, $legend, $plotArea);
        $chart->setTopLeftPosition($topLeft);
        $chart->setBottomRightPosition($bottomRight);

        return $chart;
    }

    private function buildPrizeRelativeChart(int $endRow, string $topLeft, string $bottomRight): Chart
    {
        $labels = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $this->cellRef('獎項統計', 'J', 1),
                null,
                1
            ),
        ];
        $categories = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $this->rangeRef('獎項統計', 'B', 2, $endRow),
                null,
                $this->prizeCount,
                $this->prizeNames
            ),
        ];
        $values = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                $this->rangeRef('獎項統計', 'J', 2, $endRow),
                null,
                $this->prizeCount,
                $this->prizeRelativeRates
            ),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($values) - 1),
            $labels,
            $categories,
            $values
        );
        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $title = new Title('獎項相對中獎率(倍)');

        $chart = new Chart('prize_relative_rate', $title, $legend, $plotArea);
        $chart->setTopLeftPosition($topLeft);
        $chart->setBottomRightPosition($bottomRight);

        return $chart;
    }

    private function buildDepartmentShareChart(int $endRow, string $topLeft, string $bottomRight): Chart
    {
        $labels = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $this->cellRef('部門比較', 'C', 1),
                null,
                1
            ),
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $this->cellRef('部門比較', 'E', 1),
                null,
                1
            ),
        ];
        $categories = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $this->rangeRef('部門比較', 'A', 2, $endRow),
                null,
                $this->departmentCount,
                $this->departmentNames
            ),
        ];
        $values = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                $this->rangeRef('部門比較', 'C', 2, $endRow),
                null,
                $this->departmentCount,
                $this->departmentEligiblePct
            ),
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                $this->rangeRef('部門比較', 'E', 2, $endRow),
                null,
                $this->departmentCount,
                $this->departmentWinPct
            ),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($values) - 1),
            $labels,
            $categories,
            $values
        );
        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $title = new Title('部門可抽占比 vs 中獎占比');

        $chart = new Chart('department_share', $title, $legend, $plotArea);
        $chart->setTopLeftPosition($topLeft);
        $chart->setBottomRightPosition($bottomRight);

        return $chart;
    }

    private function buildDepartmentDeltaChart(int $endRow, string $topLeft, string $bottomRight): Chart
    {
        $labels = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $this->cellRef('部門比較', 'F', 1),
                null,
                1
            ),
        ];
        $categories = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $this->rangeRef('部門比較', 'A', 2, $endRow),
                null,
                $this->departmentCount,
                $this->departmentNames
            ),
        ];
        $values = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                $this->rangeRef('部門比較', 'F', 2, $endRow),
                null,
                $this->departmentCount,
                $this->departmentDeltaPct
            ),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($values) - 1),
            $labels,
            $categories,
            $values
        );
        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $title = new Title('部門占比差異(%)');

        $chart = new Chart('department_delta', $title, $legend, $plotArea);
        $chart->setTopLeftPosition($topLeft);
        $chart->setBottomRightPosition($bottomRight);

        return $chart;
    }

    private function cellRef(string $sheet, string $column, int $row): string
    {
        return sprintf("'%s'!$%s$%d", $sheet, $column, $row);
    }

    private function rangeRef(string $sheet, string $column, int $startRow, int $endRow): string
    {
        return sprintf("'%s'!$%s$%d:$%s$%d", $sheet, $column, $startRow, $column, $endRow);
    }
}

final class LotteryAnalysisPivotSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function title(): string
    {
        return 'PivotData';
    }

    public function headings(): array
    {
        return [
            '類別',
            '名稱',
            '指標',
            '值',
            '單位',
            '模擬次數',
            '生成時間',
            '活動名稱',
            'Brand Code',
            '分析ID',
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }
}
