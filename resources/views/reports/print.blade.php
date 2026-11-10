<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportTitle ?? 'Dinamik Analiz Raporu' }} - KÖKSAN HEDEFLER</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        body {
            background-color: #f8fafc;
            color: #1e293b;
            padding: 24px;
            font-size: 12px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .logo-title h1 {
            font-size: 18px;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 4px;
        }
        .logo-title p {
            font-size: 11px;
            color: #64748b;
        }
        .meta-info {
            text-align: right;
            font-size: 11px;
            color: #475569;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .summary-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
        }
        .summary-card .label {
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .summary-card .val {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            background: #ffffff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border: 1px solid #e2e8f0;
        }
        th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        tbody tr:nth-child(even) { background-color: #f8fafc; }
        .total-row {
            background-color: #e0e7ff !important;
            font-weight: 700;
            border-top: 2px solid #6366f1;
            border-bottom: 2px solid #6366f1;
        }
        .footer {
            margin-top: 32px;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #94a3b8;
        }
        .no-print-bar {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        @media print {
            body { background: #ffffff; padding: 0; }
            .no-print-bar { display: none !important; }
            @page { size: landscape; margin: 12mm; }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <strong>Yazdırma & PDF Çıktı Önizlemesi</strong>
            <span style="color: #64748b; margin-left: 8px;">Tarayıcınızın 'Yazdır' iletişim kutusundan 'PDF Olarak Kaydet' seçeneğini kullanabilirsiniz.</span>
        </div>
        <button class="btn-print" onclick="window.print()">🖨️ Yazdır / PDF İndir</button>
    </div>

    @php
        $mode = $mode ?? ($reportResult['mode'] ?? 'standard');
    @endphp

    {{-- ─── 1. ÇOK BOYUTLU PİVOT YAZDIRMA GÖRÜNÜMÜ ─── --}}
    @if($mode === 'multi_pivot')
        @php
            $rowDimNames = $reportResult['row_dimension_names'] ?? ['Kategori'];
            $colDimName = $reportResult['col_dimension_name'] ?? 'Sütun';
            $columns = $reportResult['columns'] ?? ['Genel Toplam'];
            $rows = $reportResult['rows'] ?? [];
            $columnTotals = $reportResult['column_totals'] ?? [];
            $grandTotal = $reportResult['grand_total'] ?? 0;
            $metricTitle = $reportResult['full_metric_title'] ?? 'Metrik';
            $summary = $reportResult['summary'] ?? [];
        @endphp

        <div class="header">
            <div class="logo-title">
                <h1>KÖKSAN HEDEFLER &bull; {{ $reportTitle ?? 'Çok Boyutlu Pivot Analizi' }}</h1>
                <p>Metrik: <strong>{{ $metricTitle }}</strong> | Boyutlar: <strong>{{ implode(' > ', $rowDimNames) }}</strong></p>
            </div>
            <div class="meta-info">
                <div>Rapor Tarihi: <strong>{{ now()->format('d.m.Y H:i') }}</strong></div>
                <div>Oluşturan: <strong>{{ Auth::user()?->name ?? 'Sistem' }}</strong></div>
            </div>
        </div>

        @if(!empty($reportResult['status_scope']))
            @php $sc = $reportResult['status_scope']; @endphp
            <div style="background-color: {{ $sc['is_multi'] ? '#fffbeb' : ($sc['is_default'] ? '#ecfdf5' : '#f8fafc') }}; border: 1px solid {{ $sc['is_multi'] ? '#fde68a' : ($sc['is_default'] ? '#a7f3d0' : '#e2e8f0') }}; border-radius: 6px; padding: 8px 12px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 11px; font-weight: 700; color: #1e293b;">
                    <strong style="color: {{ $sc['is_multi'] ? '#b45309' : ($sc['is_default'] ? '#047857' : '#475569') }};">📌 RAPOR KAPSAMI:</strong>
                    {{ $sc['description_text'] }}
                </div>
                <div style="display: flex; gap: 6px;">
                    @foreach($sc['badges'] as $b)
                        <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; background-color: {{ $b['hex'] }}15; color: {{ $b['hex'] }}; border: 1px solid {{ $b['hex'] }}40;">
                            ● {{ $b['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="summary-cards">
            <div class="summary-card">
                <div class="label">Toplam Boyut Satırı</div>
                <div class="val">{{ count($rows) }}</div>
            </div>
            <div class="summary-card">
                <div class="label">İncelenen Satır</div>
                <div class="val">{{ number_format($summary['analyzed_records'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Sütun Boyutu</div>
                <div class="val" style="font-size: 13px;">{{ $colDimName }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Genel Toplam</div>
                <div class="val" style="color: #2563eb;">{{ number_format((float)$grandTotal, 2, ',', '.') }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    @foreach($rowDimNames as $rDim)
                        <th>{{ $rDim }}</th>
                    @endforeach
                    @foreach($columns as $col)
                        <th class="text-right">{{ $col }}</th>
                    @endforeach
                    <th class="text-right" style="background-color: #172554;">GENEL TOPLAM</th>
                    <th class="text-right" style="background-color: #312e81;">% PENETRASYON</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                    <tr>
                        @foreach(($r['dim_values'] ?? []) as $dVal)
                            <td class="font-bold">{{ $dVal }}</td>
                        @endforeach
                        @foreach($columns as $col)
                            <td class="text-right">{{ number_format((float)($r['values'][$col] ?? 0), 2, ',', '.') }}</td>
                        @endforeach
                        <td class="text-right font-bold" style="background-color: #f8fafc;">
                            {{ number_format((float)($r['row_total'] ?? 0), 2, ',', '.') }}
                        </td>
                        <td class="text-right font-bold" style="background-color: #eef2ff;">
                            %{{ number_format((float)($r['penetration_share'] ?? 0), 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="{{ count($rowDimNames) }}">GENEL TOPLAM</td>
                    @foreach($columns as $col)
                        <td class="text-right">{{ number_format((float)($columnTotals[$col] ?? 0), 2, ',', '.') }}</td>
                    @endforeach
                    <td class="text-right font-bold">{{ number_format((float)$grandTotal, 2, ',', '.') }}</td>
                    <td class="text-right font-bold">%100,00</td>
                </tr>
            </tbody>
        </table>

    {{-- ─── 2. YILLIK KIYASLAMA (YoY) YAZDIRMA GÖRÜNÜMÜ ─── --}}
    @elseif($mode === 'yoy')
        @php
            $y1 = $reportResult['year_1'] ?? '2026';
            $y2 = $reportResult['year_2'] ?? '2027';
            $entityName = $reportResult['entity_dim_name'] ?? 'Personel';
            $metricTitle = $reportResult['full_metric_title'] ?? 'Metrik';
            $summary = $reportResult['summary'] ?? [];
            $items = $reportResult['items'] ?? [];
        @endphp

        <div class="header">
            <div class="logo-title">
                <h1>KÖKSAN HEDEFLER &bull; {{ $reportTitle ?? "Yıllık Kıyaslama ({$y1} vs {$y2})" }}</h1>
                <p>Kıyaslanan Boyut: <strong>{{ $entityName }}</strong> | Metrik: <strong>{{ $metricTitle }}</strong></p>
            </div>
            <div class="meta-info">
                <div>Rapor Tarihi: <strong>{{ now()->format('d.m.Y H:i') }}</strong></div>
                <div>Oluşturan: <strong>{{ Auth::user()?->name ?? 'Sistem' }}</strong></div>
            </div>
        </div>

        @if(!empty($reportResult['status_scope']))
            @php $sc = $reportResult['status_scope']; @endphp
            <div style="background-color: {{ $sc['is_multi'] ? '#fffbeb' : ($sc['is_default'] ? '#ecfdf5' : '#f8fafc') }}; border: 1px solid {{ $sc['is_multi'] ? '#fde68a' : ($sc['is_default'] ? '#a7f3d0' : '#e2e8f0') }}; border-radius: 6px; padding: 8px 12px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 11px; font-weight: 700; color: #1e293b;">
                    <strong style="color: {{ $sc['is_multi'] ? '#b45309' : ($sc['is_default'] ? '#047857' : '#475569') }};">📌 RAPOR KAPSAMI:</strong>
                    {{ $sc['description_text'] }}
                </div>
                <div style="display: flex; gap: 6px;">
                    @foreach($sc['badges'] as $b)
                        <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; background-color: {{ $b['hex'] }}15; color: {{ $b['hex'] }}; border: 1px solid {{ $b['hex'] }}40;">
                            ● {{ $b['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="summary-cards">
            <div class="summary-card">
                <div class="label">{{ $y1 }} Toplamı</div>
                <div class="val">{{ number_format((float)($summary['total_y1'] ?? 0), 2, ',', '.') }}</div>
            </div>
            <div class="summary-card">
                <div class="label">{{ $y2 }} Toplamı</div>
                <div class="val">{{ number_format((float)($summary['total_y2'] ?? 0), 2, ',', '.') }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Net Değişim (Fark)</div>
                <div class="val" style="color: {{ ($summary['total_delta'] ?? 0) >= 0 ? '#059669' : '#dc2626' }};">
                    {{ ($summary['total_delta'] ?? 0) > 0 ? '+' : '' }}{{ number_format((float)($summary['total_delta'] ?? 0), 2, ',', '.') }}
                </div>
            </div>
            <div class="summary-card">
                <div class="label">Net Büyüme Oranı</div>
                <div class="val" style="color: {{ ($summary['total_growth_rate'] ?? 0) >= 0 ? '#059669' : '#dc2626' }};">
                    {{ ($summary['total_growth_rate'] ?? 0) > 0 ? '+' : '' }}{{ $summary['total_growth_rate'] ?? 0 }}%
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>{{ $entityName }}</th>
                    <th class="text-right">{{ $y1 }} Değeri</th>
                    <th class="text-right">{{ $y2 }} Değeri</th>
                    <th class="text-right">Fark (Değişim)</th>
                    <th class="text-right">% Büyüme / Değişim</th>
                    <th class="text-right">{{ $y1 }} Payı (%)</th>
                    <th class="text-right">{{ $y2 }} Payı (%)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $it)
                    <tr>
                        <td class="font-bold">{{ $it['name'] }}</td>
                        <td class="text-right">{{ number_format((float)$it['val_1'], 2, ',', '.') }}</td>
                        <td class="text-right font-bold">{{ number_format((float)$it['val_2'], 2, ',', '.') }}</td>
                        <td class="text-right font-bold" style="color: {{ $it['delta'] >= 0 ? '#059669' : '#dc2626' }};">
                            {{ $it['delta'] > 0 ? '+' : '' }}{{ number_format((float)$it['delta'], 2, ',', '.') }}
                        </td>
                        <td class="text-right font-bold" style="color: {{ $it['growth_rate'] >= 0 ? '#059669' : '#dc2626' }};">
                            {{ $it['growth_rate'] > 0 ? '+' : '' }}{{ $it['growth_rate'] }}%
                        </td>
                        <td class="text-right">%{{ $it['share_1'] }}</td>
                        <td class="text-right font-bold">%{{ $it['share_2'] }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>GENEL TOPLAM</td>
                    <td class="text-right">{{ number_format((float)($summary['total_y1'] ?? 0), 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format((float)($summary['total_y2'] ?? 0), 2, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ number_format((float)($summary['total_delta'] ?? 0), 2, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ ($summary['total_growth_rate'] ?? 0) > 0 ? '+' : '' }}{{ $summary['total_growth_rate'] ?? 0 }}%</td>
                    <td class="text-right">%100,0</td>
                    <td class="text-right">%100,0</td>
                </tr>
            </tbody>
        </table>

    {{-- ─── 3. COĞRAFİ DÜNYA HARİTASI YAZDIRMA GÖRÜNÜMÜ ─── --}}
    @elseif($mode === 'geo')
        @php
            $rankings = $reportResult['rankings'] ?? [];
            $summary = $reportResult['summary'] ?? [];
            $metricTitle = $reportResult['full_metric_title'] ?? 'Metrik';
        @endphp

        <div class="header">
            <div class="logo-title">
                <h1>KÖKSAN HEDEFLER &bull; {{ $reportTitle ?? 'Coğrafi Dünya Satış Dağılımı' }}</h1>
                <p>Metrik: <strong>{{ $metricTitle }}</strong> | Ülke Sayısı: <strong>{{ count($rankings) }}</strong></p>
            </div>
            <div class="meta-info">
                <div>Rapor Tarihi: <strong>{{ now()->format('d.m.Y H:i') }}</strong></div>
                <div>Oluşturan: <strong>{{ Auth::user()?->name ?? 'Sistem' }}</strong></div>
            </div>
        </div>

        @if(!empty($reportResult['status_scope']))
            @php $sc = $reportResult['status_scope']; @endphp
            <div style="background-color: {{ $sc['is_multi'] ? '#fffbeb' : ($sc['is_default'] ? '#ecfdf5' : '#f8fafc') }}; border: 1px solid {{ $sc['is_multi'] ? '#fde68a' : ($sc['is_default'] ? '#a7f3d0' : '#e2e8f0') }}; border-radius: 6px; padding: 8px 12px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 11px; font-weight: 700; color: #1e293b;">
                    <strong style="color: {{ $sc['is_multi'] ? '#b45309' : ($sc['is_default'] ? '#047857' : '#475569') }};">📌 RAPOR KAPSAMI:</strong>
                    {{ $sc['description_text'] }}
                </div>
                <div style="display: flex; gap: 6px;">
                    @foreach($sc['badges'] as $b)
                        <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; background-color: {{ $b['hex'] }}15; color: {{ $b['hex'] }}; border: 1px solid {{ $b['hex'] }}40;">
                            ● {{ $b['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="summary-cards">
            <div class="summary-card">
                <div class="label">İşlem Yapılan Ülke</div>
                <div class="val">{{ $summary['total_countries'] ?? 0 }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Toplam Kayıt / Sipariş</div>
                <div class="val">{{ number_format($summary['total_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Lider Ülke</div>
                <div class="val" style="font-size: 13px;">{{ $summary['top_country']['name'] ?? '-' }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Global Toplam</div>
                <div class="val" style="color: #2563eb;">{{ number_format((float)($summary['global_total'] ?? 0), 2, ',', '.') }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 45px; text-align: center;">Sıra</th>
                    <th>Ülke Adı</th>
                    <th style="width: 60px; text-align: center;">ISO</th>
                    <th class="text-right">Toplam Hacim</th>
                    <th class="text-right">Pazar Payı (%)</th>
                    <th class="text-right">Sipariş Sayısı</th>
                    <th>İşlem Yapan Personeller</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rankings as $r)
                    <tr>
                        <td class="text-center font-bold" style="color: #64748b;">#{{ $r['rank'] ?? '' }}</td>
                        <td class="font-bold">{{ $r['name'] }}</td>
                        <td class="text-center">{{ $r['code'] }}</td>
                        <td class="text-right font-bold">{{ number_format((float)$r['total'], 2, ',', '.') }}</td>
                        <td class="text-right font-bold" style="color: #4f46e5;">%{{ $r['share'] }}</td>
                        <td class="text-right">{{ number_format($r['count'], 0, ',', '.') }}</td>
                        <td>{{ implode(', ', $r['top_users'] ?? []) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3">GENEL TOPLAM</td>
                    <td class="text-right font-bold">{{ number_format((float)($summary['global_total'] ?? 0), 2, ',', '.') }}</td>
                    <td class="text-right font-bold">%100,00</td>
                    <td class="text-right">{{ number_format($summary['total_rows'] ?? 0, 0, ',', '.') }}</td>
                    <td>-</td>
                </tr>
            </tbody>
        </table>

    {{-- ─── 4. SEGMENT A vs B BENCHMARK YAZDIRMA GÖRÜNÜMÜ ─── --}}
    @elseif($mode === 'segment')
        @php
            $segA = $reportResult['segment_a'] ?? [];
            $segB = $reportResult['segment_b'] ?? [];
            $titleA = $segA['title'] ?? 'Segment A';
            $titleB = $segB['title'] ?? 'Segment B';
            $matrix = $reportResult['personnel_matrix'] ?? [];
            $metricTitle = $reportResult['full_metric_title'] ?? 'Metrik';
        @endphp

        <div class="header">
            <div class="logo-title">
                <h1>KÖKSAN HEDEFLER &bull; {{ $reportTitle ?? 'Kümeler Arası Segment Kıyaslama' }}</h1>
                <p>[{{ $titleA }}] vs [{{ $titleB }}] | Metrik: <strong>{{ $metricTitle }}</strong></p>
            </div>
            <div class="meta-info">
                <div>Rapor Tarihi: <strong>{{ now()->format('d.m.Y H:i') }}</strong></div>
                <div>Oluşturan: <strong>{{ Auth::user()?->name ?? 'Sistem' }}</strong></div>
            </div>
        </div>

        @if(!empty($reportResult['status_scope']))
            @php $sc = $reportResult['status_scope']; @endphp
            <div style="background-color: {{ $sc['is_multi'] ? '#fffbeb' : ($sc['is_default'] ? '#ecfdf5' : '#f8fafc') }}; border: 1px solid {{ $sc['is_multi'] ? '#fde68a' : ($sc['is_default'] ? '#a7f3d0' : '#e2e8f0') }}; border-radius: 6px; padding: 8px 12px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 11px; font-weight: 700; color: #1e293b;">
                    <strong style="color: {{ $sc['is_multi'] ? '#b45309' : ($sc['is_default'] ? '#047857' : '#475569') }};">📌 RAPOR KAPSAMI:</strong>
                    {{ $sc['description_text'] }}
                </div>
                <div style="display: flex; gap: 6px;">
                    @foreach($sc['badges'] as $b)
                        <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; background-color: {{ $b['hex'] }}15; color: {{ $b['hex'] }}; border: 1px solid {{ $b['hex'] }}40;">
                            ● {{ $b['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="summary-cards">
            <div class="summary-card">
                <div class="label">{{ $titleA }} Toplamı</div>
                <div class="val">{{ number_format((float)($segA['total'] ?? 0), 2, ',', '.') }}</div>
            </div>
            <div class="summary-card">
                <div class="label">{{ $titleB }} Toplamı</div>
                <div class="val">{{ number_format((float)($segB['total'] ?? 0), 2, ',', '.') }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Hacim Farkı</div>
                <div class="val">{{ number_format((float)(($segA['total'] ?? 0) - ($segB['total'] ?? 0)), 2, ',', '.') }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Lider Segment</div>
                <div class="val" style="font-size: 13px; color: #4f46e5;">{{ ($segA['total'] ?? 0) >= ($segB['total'] ?? 0) ? $titleA : $titleB }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Personel Adı</th>
                    <th class="text-right">{{ $titleA }} Satışı</th>
                    <th class="text-right">{{ $titleB }} Satışı</th>
                    <th class="text-right">{{ $titleA }} Payı (%)</th>
                    <th class="text-right">{{ $titleB }} Payı (%)</th>
                    <th class="text-center">Ağırlık Tercihi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($matrix as $pm)
                    <tr>
                        <td class="font-bold">{{ $pm['user_name'] }}</td>
                        <td class="text-right">{{ number_format((float)$pm['val_a'], 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format((float)$pm['val_b'], 2, ',', '.') }}</td>
                        <td class="text-right">%{{ $pm['share_a'] }}</td>
                        <td class="text-right">%{{ $pm['share_b'] }}</td>
                        <td class="text-center font-bold">{{ $pm['preference'] }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>GENEL TOPLAM</td>
                    <td class="text-right font-bold">{{ number_format((float)($segA['total'] ?? 0), 2, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ number_format((float)($segB['total'] ?? 0), 2, ',', '.') }}</td>
                    <td class="text-right font-bold">%100,0</td>
                    <td class="text-right font-bold">%100,0</td>
                    <td class="text-center">-</td>
                </tr>
            </tbody>
        </table>

    {{-- ─── 5. STANDART PİVOT YAZDIRMA GÖRÜNÜMÜ ─── --}}
    @else
        @php
            $summary = $reportResult['summary'] ?? [];
            $pivot = $reportResult['pivotTable'] ?? [];
            $dimXName = $pivot['dim_x_name'] ?? 'Kategori';
            $seriesList = $pivot['series_list'] ?? ['Genel Toplam'];
            $categories = $pivot['categories'] ?? [];
            $matrix = $pivot['matrix'] ?? [];
            $rowTotals = $pivot['row_totals'] ?? [];
            $columnTotals = $pivot['column_totals'] ?? [];
            $grandTotal = $pivot['grand_total'] ?? 0;
            $isMultiSeries = (count($seriesList) > 1 || ($seriesList[0] ?? '') !== 'Genel Toplam');
        @endphp

        <div class="header">
            <div class="logo-title">
                <h1>KÖKSAN HEDEFLER &bull; {{ $reportTitle ?? 'Dinamik Analiz Raporu' }}</h1>
                <p>Şablon: <strong>{{ $summary['template_name'] ?? 'Genel' }}</strong> | Metrik: <strong>{{ $summary['full_metric_title'] ?? 'Metrik' }}</strong></p>
            </div>
            <div class="meta-info">
                <div>Rapor Tarihi: <strong>{{ $summary['generated_at'] ?? now()->format('d.m.Y H:i') }}</strong></div>
                <div>Oluşturan: <strong>{{ Auth::user()?->name ?? 'Sistem' }}</strong></div>
            </div>
        </div>

        @if(!empty($reportResult['status_scope']))
            @php $sc = $reportResult['status_scope']; @endphp
            <div style="background-color: {{ $sc['is_multi'] ? '#fffbeb' : ($sc['is_default'] ? '#ecfdf5' : '#f8fafc') }}; border: 1px solid {{ $sc['is_multi'] ? '#fde68a' : ($sc['is_default'] ? '#a7f3d0' : '#e2e8f0') }}; border-radius: 6px; padding: 8px 12px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 11px; font-weight: 700; color: #1e293b;">
                    <strong style="color: {{ $sc['is_multi'] ? '#b45309' : ($sc['is_default'] ? '#047857' : '#475569') }};">📌 RAPOR KAPSAMI:</strong>
                    {{ $sc['description_text'] }}
                </div>
                <div style="display: flex; gap: 6px;">
                    @foreach($sc['badges'] as $b)
                        <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; background-color: {{ $b['hex'] }}15; color: {{ $b['hex'] }}; border: 1px solid {{ $b['hex'] }}40;">
                            ● {{ $b['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="summary-cards">
            <div class="summary-card">
                <div class="label">İncelenen Form</div>
                <div class="val">{{ number_format($summary['total_entries'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="summary-card">
                <div class="label">İncelenen Satır</div>
                <div class="val">{{ number_format($summary['total_rows'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Kategori Çeşitliliği</div>
                <div class="val">{{ count($categories) }} {{ $dimXName }}</div>
            </div>
            <div class="summary-card">
                <div class="label">Genel Toplam ({{ $summary['aggregation_name'] ?? 'Toplam' }})</div>
                <div class="val" style="color: #2563eb;">{{ number_format((float)$grandTotal, 2, ',', '.') }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="min-width: 180px;">{{ $dimXName }}</th>
                    @if($isMultiSeries)
                        @foreach($seriesList as $ser)
                            <th class="text-right">{{ $ser }}</th>
                        @endforeach
                    @endif
                    <th class="text-right" style="width: 130px; background-color: #172554;">TOPLAM</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $cat)
                    <tr>
                        <td class="font-bold">{{ $cat }}</td>
                        @if($isMultiSeries)
                            @foreach($seriesList as $ser)
                                <td class="text-right">{{ number_format((float)($matrix[$cat][$ser] ?? 0), 2, ',', '.') }}</td>
                            @endforeach
                        @endif
                        <td class="text-right font-bold" style="background-color: #f1f5f9;">
                            {{ number_format((float)($rowTotals[$cat] ?? 0), 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>GENEL TOPLAM</td>
                    @if($isMultiSeries)
                        @foreach($seriesList as $ser)
                            <td class="text-right">{{ number_format((float)($columnTotals[$ser] ?? 0), 2, ',', '.') }}</td>
                        @endforeach
                    @endif
                    <td class="text-right font-bold">{{ number_format((float)$grandTotal, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="footer">
        <div>Bu rapor KÖKSAN Hedefler & Dinamik Raporlama Portalı tarafından otomatik oluşturulmuştur.</div>
        <div>Sayfa 1 / 1 &bull; kys.koksan.com</div>
    </div>

</body>
</html>
