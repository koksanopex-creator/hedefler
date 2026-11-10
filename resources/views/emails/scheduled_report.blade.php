<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle ?? 'Rapor Bildirimi' }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f1f5f9;
            color: #334155;
            margin: 0;
            padding: 24px;
        }
        .container {
            max-width: 680px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 0;
            font-size: 13px;
            opacity: 0.9;
        }
        .content {
            padding: 24px;
        }
        .badge {
            display: inline-block;
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .intro-text {
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 24px;
            background-color: #f8fafc;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .stat-item {
            display: table-cell;
            padding: 12px;
            text-align: center;
            border-right: 1px solid #e2e8f0;
        }
        .stat-item:last-child {
            border-right: none;
        }
        .stat-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
        }
        .stat-value {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 4px;
        }
        table.preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 24px;
        }
        table.preview-table th, table.preview-table td {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            text-align: left;
        }
        table.preview-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 11px;
        }
        table.preview-table tr.total-row {
            background-color: #e0e7ff;
            font-weight: bold;
        }
        .btn-action {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            text-align: center;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 16px 24px;
            font-size: 11px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>KÖKSAN HEDEFLER &bull; RAPOR BİLDİRİMİ</h1>
        <p>{{ $reportTitle ?? 'Dinamik Analiz Raporu' }}</p>
    </div>

    <div class="content">
        <span class="badge">{{ $scheduleFrequencyName ?? 'Zamanlanmış Rapor' }}</span>
        
        <p class="intro-text">
            Merhaba,
            <br><br>
            Sistemde tanımlı olan <strong>{{ $reportTitle }}</strong> başlıklı raporunuz başarıyla hazırlanmıştır. Özet bilgileri aşağıda bulabilir, detaylı analize ekteki Excel dosyasından veya portaldan erişebilirsiniz.
        </p>

        @if(!empty($reportResult['status_scope']))
            @php $sc = $reportResult['status_scope']; @endphp
            <div style="background-color: {{ $sc['is_multi'] ? '#fffbeb' : ($sc['is_default'] ? '#ecfdf5' : '#f8fafc') }}; border: 1px solid {{ $sc['is_multi'] ? '#fde68a' : ($sc['is_default'] ? '#a7f3d0' : '#e2e8f0') }}; border-radius: 6px; padding: 10px 14px; margin-bottom: 20px;">
                <div style="font-size: 12px; font-weight: bold; color: #1e293b; margin-bottom: 6px;">
                    <span style="color: {{ $sc['is_multi'] ? '#b45309' : ($sc['is_default'] ? '#047857' : '#475569') }};">📌 RAPOR KAPSAMI:</span>
                    {{ $sc['description_text'] }}
                </div>
                <div style="margin-top: 4px;">
                    @foreach($sc['badges'] as $b)
                        <span style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; background-color: {{ $b['hex'] }}15; color: {{ $b['hex'] }}; border: 1px solid {{ $b['hex'] }}40; margin-right: 4px;">
                            ● {{ $b['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        @php
            $mode = $reportResult['mode'] ?? 'standard';
            $summary = $reportResult['summary'] ?? [];
            $metricTitle = $reportResult['full_metric_title'] ?? ($summary['full_metric_title'] ?? 'Metrik');
        @endphp

        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-label">Analiz Modu</div>
                <div class="stat-value" style="font-size: 13px;">
                    {{ match($mode) { 'multi_pivot' => 'Çok Boyutlu Pivot', 'yoy' => 'Yıllık Kıyaslama', 'geo' => 'Dünya Haritası', 'segment' => 'Segment Kıyaslama', default => 'Standart Pivot' } }}
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Analiz Metriği</div>
                <div class="stat-value" style="font-size: 13px;">{{ $metricTitle }}</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Toplam Hacim</div>
                <div class="stat-value" style="color: #2563eb;">
                    {{ number_format((float)($reportResult['grand_total'] ?? ($summary['grand_total'] ?? ($summary['global_total'] ?? 0))), 2, ',', '.') }}
                </div>
            </div>
        </div>

        <h3 style="font-size: 14px; margin-bottom: 8px; color: #1e3a8a;">📊 Rapor Özeti Tablosu</h3>
        
        @if($mode === 'multi_pivot')
            @php
                $rows = array_slice($reportResult['rows'] ?? [], 0, 15);
                $columns = $reportResult['columns'] ?? ['Genel Toplam'];
            @endphp
            <table class="preview-table">
                <thead>
                    <tr>
                        <th>Boyut Kırılımı</th>
                        <th style="text-align: right; width: 140px;">Toplam</th>
                        <th style="text-align: right; width: 100px;">% Pay</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $r)
                        <tr>
                            <td>{{ $r['composite_key'] }}</td>
                            <td style="text-align: right; font-weight: bold;">{{ number_format((float)($r['row_total'] ?? 0), 2, ',', '.') }}</td>
                            <td style="text-align: right; color: #4f46e5;">%{{ number_format((float)($r['penetration_share'] ?? 0), 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>GENEL TOPLAM</td>
                        <td style="text-align: right;">{{ number_format((float)($reportResult['grand_total'] ?? 0), 2, ',', '.') }}</td>
                        <td style="text-align: right;">%100,00</td>
                    </tr>
                </tbody>
            </table>
        @elseif($mode === 'yoy')
            @php
                $items = array_slice($reportResult['items'] ?? [], 0, 15);
                $y1 = $reportResult['year_1'] ?? 'Yıl 1';
                $y2 = $reportResult['year_2'] ?? 'Yıl 2';
            @endphp
            <table class="preview-table">
                <thead>
                    <tr>
                        <th>{{ $reportResult['entity_dim_name'] ?? 'Varlık' }}</th>
                        <th style="text-align: right;">{{ $y1 }}</th>
                        <th style="text-align: right;">{{ $y2 }}</th>
                        <th style="text-align: right;">Fark</th>
                        <th style="text-align: right;">% Büyüme</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $it)
                        <tr>
                            <td>{{ $it['name'] }}</td>
                            <td style="text-align: right;">{{ number_format((float)$it['val_1'], 2, ',', '.') }}</td>
                            <td style="text-align: right; font-weight: bold;">{{ number_format((float)$it['val_2'], 2, ',', '.') }}</td>
                            <td style="text-align: right; color: {{ $it['delta'] >= 0 ? '#059669' : '#dc2626' }};">
                                {{ $it['delta'] > 0 ? '+' : '' }}{{ number_format((float)$it['delta'], 2, ',', '.') }}
                            </td>
                            <td style="text-align: right;">{{ $it['growth_rate'] > 0 ? '+' : '' }}{{ $it['growth_rate'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @elseif($mode === 'geo')
            @php
                $rankings = array_slice($reportResult['rankings'] ?? [], 0, 10);
            @endphp
            <table class="preview-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">Sıra</th>
                        <th>Ülke Adı</th>
                        <th style="text-align: right;">Toplam Değer</th>
                        <th style="text-align: right;">Pazar Payı</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rankings as $rk)
                        <tr>
                            <td style="text-align: center;">#{{ $rk['rank'] }}</td>
                            <td>{{ $rk['name'] }} ({{ $rk['code'] }})</td>
                            <td style="text-align: right; font-weight: bold;">{{ number_format((float)$rk['total'], 2, ',', '.') }}</td>
                            <td style="text-align: right; color: #4f46e5;">%{{ $rk['share'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @elseif($mode === 'segment')
            @php
                $matrix = array_slice($reportResult['personnel_matrix'] ?? [], 0, 10);
                $titleA = $reportResult['segment_a']['title'] ?? 'Segment A';
                $titleB = $reportResult['segment_b']['title'] ?? 'Segment B';
            @endphp
            <table class="preview-table">
                <thead>
                    <tr>
                        <th>Personel</th>
                        <th style="text-align: right;">{{ $titleA }}</th>
                        <th style="text-align: right;">{{ $titleB }}</th>
                        <th style="text-align: center;">Tercih</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($matrix as $pm)
                        <tr>
                            <td>{{ $pm['user_name'] }}</td>
                            <td style="text-align: right;">{{ number_format((float)$pm['val_a'], 2, ',', '.') }}</td>
                            <td style="text-align: right;">{{ number_format((float)$pm['val_b'], 2, ',', '.') }}</td>
                            <td style="text-align: center;">{{ $pm['preference'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            @php
                $pivot = $reportResult['pivotTable'] ?? [];
                $dimXName = $pivot['dim_x_name'] ?? 'Kategori';
                $categories = array_slice($pivot['categories'] ?? [], 0, 15);
                $rowTotals = $pivot['row_totals'] ?? [];
                $grandTotal = $pivot['grand_total'] ?? 0;
            @endphp
            <table class="preview-table">
                <thead>
                    <tr>
                        <th>{{ $dimXName }}</th>
                        <th style="text-align: right; width: 140px;">{{ $summary['full_metric_title'] ?? 'Toplam Değer' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $cat)
                        <tr>
                            <td>{{ $cat }}</td>
                            <td style="text-align: right;">{{ number_format((float)($rowTotals[$cat] ?? 0), 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>GENEL TOPLAM</td>
                        <td style="text-align: right;">{{ number_format((float)$grandTotal, 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        @if(!empty($reportUrl))
            <div style="text-align: center; margin: 28px 0 16px 0;">
                <a href="{{ $reportUrl }}" class="btn-action">🌐 Hedefler Portalında Raporu Görüntüle</a>
            </div>
        @endif
    </div>

    <div class="footer">
        Bu e-posta KÖKSAN Hedefler Otomasyonu tarafından otomatik olarak gönderilmiştir. Lütfen doğrudan yanıtlamayınız.
        <br>
        &copy; {{ date('Y') }} KÖKSAN Pet ve Plastik Ambalaj San. A.Ş.
    </div>
</div>

</body>
</html>

