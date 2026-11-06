<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hedefler Portalı - Uygulama Başvurusu</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: #ffffff;
            width: 100%;
            max-width: 580px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08), 0 20px 48px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
            padding: 32px 30px;
            color: #ffffff;
            text-align: center;
            position: relative;
        }
        .header h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        .badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }
        .body {
            padding: 30px;
        }
        .alert-info {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 13px;
            color: #1e40af;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        .form-control-readonly {
            width: 100%;
            padding: 11px 14px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            color: #475569;
            font-weight: 500;
            cursor: not-allowed;
        }
        .readonly-hint {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        textarea.form-control {
            width: 100%;
            padding: 12px 14px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            color: #1e293b;
            resize: vertical;
            min-height: 90px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        textarea.form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        .btn-submit {
            display: block;
            width: 100%;
            padding: 13px 20px;
            background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            text-align: center;
            text-decoration: none;
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
        }
        .btn-cancel {
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            background: transparent;
            color: #64748b;
            border: none;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: color 0.15s;
        }
        .btn-cancel:hover {
            color: #1e293b;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <span class="badge">KÖKSAN MERKEZİ SSO</span>
        <h1>Hedefler Portalı Başvurusu</h1>
        <p>Hedef ve Performans Yönetim Sistemine erişim talebi oluşturun.</p>
    </div>

    <div class="body">
        <div class="alert-info">
            <strong>Bilgilendirme:</strong> Kimlik ve departman bilgileriniz Merkezi Yönetim Portalı üzerinden otomatik aktarılmıştır. Başvurunuz sistem yöneticisine iletilecek ve onaylandığında tarafınıza rol tanımlanacaktır.
        </div>

        <form action="{{ route('sso.basvuru.kaydet') }}" method="POST">
            @csrf

            <div class="form-group">
                <label>Ad Soyad</label>
                <input type="text" class="form-control-readonly" value="{{ trim(($centralUser['first_name'] ?? '') . ' ' . ($centralUser['last_name'] ?? '')) }}" readonly>
                <span class="readonly-hint">🔒 Merkezi API üzerinden yönetilir.</span>
            </div>

            <div class="form-group">
                <label>E-posta Adresi</label>
                <input type="text" class="form-control-readonly" value="{{ $centralUser['email'] ?? '' }}" readonly>
                <span class="readonly-hint">🔒 Merkezi API üzerinden yönetilir.</span>
            </div>

            <div class="form-group">
                <label>Departman / Bölüm</label>
                <input type="text" class="form-control-readonly" value="{{ $centralUser['department_name'] ?? 'Tanımlı Değil' }}" readonly>
                <span class="readonly-hint">🔒 Merkezi API üzerinden yönetilir.</span>
            </div>

            <div class="form-group">
                <label for="application_note">Talep Notu / Açıklama (İsteğe Bağlı)</label>
                <textarea id="application_note" name="application_note" class="form-control" placeholder="Yöneticiye iletmek istediğiniz özel bir görev tanımı veya açıklama varsa yazabilirsiniz..."></textarea>
            </div>

            <button type="submit" class="btn-submit">
                Başvuruyu İlet
            </button>

            <a href="{{ $centralUrl }}/dashboard" class="btn-cancel">
                ← Merkezi Portala Geri Dön
            </a>
        </form>
    </div>
</div>

</body>
</html>

