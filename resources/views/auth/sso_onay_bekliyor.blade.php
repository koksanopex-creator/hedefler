<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hedefler Portalı - Durum Bilgilendirmesi</title>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <link rel="alternate icon" type="image/png" href="{{ asset('favicon.png') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">
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
            max-width: 540px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08), 0 20px 48px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .header {
            padding: 36px 30px 24px;
        }
        .icon-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
        }
        /* Durum Renkleri */
        .status-passive {
            border-top: 5px solid #ef4444;
        }
        .status-pending .icon-circle {
            background: #fef3c7;
            color: #d97706;
            border: 4px solid #fde68a;
        }
        .status-closed .icon-circle {
            background: #f1f5f9;
            color: #64748b;
            border: 4px solid #cbd5e1;
        }
        .status-passive .icon-circle {
            background: #fef2f2;
            color: #b91c1c;
            border: 4px solid #fecaca;
        }
        .status-rejected-correctable .icon-circle {
            background: #ffedd5;
            color: #ea580c;
            border: 4px solid #fed7aa;
        }
        .status-rejected-permanent .icon-circle {
            background: #fee2e2;
            color: #dc2626;
            border: 4px solid #fecaca;
        }
        h2 {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }
        p.desc {
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
            padding: 0 10px;
        }
        .body {
            padding: 0 30px 32px;
        }
        .box-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin: 20px 0;
            text-align: left;
            font-size: 13px;
        }
        .box-info div {
            margin-bottom: 6px;
            color: #475569;
        }
        .box-info div:last-child {
            margin-bottom: 0;
        }
        .box-info strong {
            color: #1e293b;
        }
        .reason-box {
            background: #fff7ed;
            border-left: 4px solid #ea580c;
            border-radius: 6px;
            padding: 12px 14px;
            margin: 16px 0;
            text-align: left;
            font-size: 13px;
            color: #9a3412;
            line-height: 1.5;
        }
        .permanent-box {
            background: #fef2f2;
            border-left: 4px solid #dc2626;
            border-radius: 6px;
            padding: 12px 14px;
            margin: 16px 0;
            text-align: left;
            font-size: 13px;
            color: #991b1b;
            line-height: 1.5;
        }
        .btn-primary {
            display: block;
            width: 100%;
            padding: 12px 20px;
            background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            margin-bottom: 10px;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        .btn-secondary {
            display: block;
            width: 100%;
            padding: 12px 20px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
            color: #1e293b;
        }
    </style>
</head>
<body>

<div class="container 
    @if($state === 'account_passive') status-passive
    @elseif($state === 'permanent_rejection') status-rejected-permanent
    @elseif($state === 'correctable_rejection') status-rejected-correctable
    @elseif($state === 'applications_closed') status-closed
    @else status-pending
    @endif
">
    <div class="header">
        <div class="icon-circle">
            @if($state === 'account_passive')
                ⛔
            @elseif($state === 'permanent_rejection')
                🚫
            @elseif($state === 'correctable_rejection')
                ⚠️
            @elseif($state === 'applications_closed')
                🔒
            @elseif($state === 'pending_role')
                ⏳
            @else
                📨
            @endif
        </div>

        @if($state === 'account_passive')
            <h2>Hesabınız Pasife Alındı</h2>
            <p class="desc">Kullanıcı hesabınız sistem yöneticisi tarafından pasif duruma getirilmiştir. Hedefler sistemine erişim yetkiniz geçici olarak durdurulmuştur.</p>
        @elseif($state === 'permanent_rejection')
            <h2>Başvurunuz Kesin Olarak Reddedildi</h2>
            <p class="desc">Bu uygulama için başvurunuz sistem yöneticisi tarafından kesin olarak reddedilmiştir.</p>
        @elseif($state === 'correctable_rejection')
            <h2>Başvurunuz Düzeltme Bekliyor</h2>
            <p class="desc">Başvurunuz incelenmiş olup bazı bilgilerin düzeltilmesi gerekmektedir.</p>
        @elseif($state === 'applications_closed')
            <h2>Başvurular Şu Anda Kapalıdır</h2>
            <p class="desc">Hedefler Portalı için yeni kullanıcı başvuruları sistem yöneticisi tarafından geçici olarak kapatılmıştır.</p>
        @elseif($state === 'pending_role')
            <h2>Rolünüz Verilmedi, Onay Bekleniyor</h2>
            <p class="desc">Kullanıcı hesabınız Merkezi Sistem üzerinden Hedefler uygulamasına atanmıştır ancak sistem yöneticisi tarafından henüz bir rol tanımlanmamıştır.</p>
        @else
            <h2>Başvurunuz İnceleniyor</h2>
            <p class="desc">Başvuru talebiniz sistem yöneticisine iletilmiştir. Onay ve rol ataması yapıldığında sistem erişiminiz açılacaktır.</p>
        @endif
    </div>

    <div class="body">
        <div class="box-info">
            <div><strong>Kullanıcı:</strong> {{ trim(($centralUser['first_name'] ?? '') . ' ' . ($centralUser['last_name'] ?? '')) }}</div>
            <div><strong>E-posta:</strong> {{ $centralUser['email'] ?? '-' }}</div>
            <div><strong>Bölüm:</strong> {{ $centralUser['department_name'] ?? 'Tanımlı Değil' }}</div>
        </div>

        @if($state === 'account_passive')
            <div class="reason-box" style="background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b;">
                <strong>Bilgilendirme:</strong> Bu hesaba ait oturum ve yetkiler sistem yöneticisi tarafından durdurulmuştur. Sistem erişiminizin tekrar açılması için sistem yöneticiniz ile iletişime geçiniz.
            </div>
            <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Merkezi Portala dönerek diğer yetkili olduğunuz uygulamalara erişebilirsiniz.</p>
        @elseif($state === 'permanent_rejection')
            <div class="permanent-box">
                <strong>Gerekçeli Karar:</strong> {{ $rejectionReason ?? 'Yönetici tarafından kalıcı ret kararı verilmiştir. Yeniden başvuru yapılamaz.' }}
            </div>
            <p style="font-size: 12px; color: #94a3b8; margin-bottom: 20px;">Eğer bir hata olduğunu düşünüyorsanız lütfen sistem yöneticisi ile irtibata geçiniz.</p>
        @elseif($state === 'correctable_rejection')
            <div class="reason-box">
                <strong>Düzeltme Gerekçesi:</strong> {{ $rejectionReason ?? 'Lütfen Merkezi Sistem profilinizdeki bilgileri (ad, soyad, e-posta vb.) güncelleyerek tekrar başvurunuz.' }}
            </div>
            <a href="{{ $centralUrl }}/profile" target="_blank" class="btn-primary" style="background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);">
                ✏️ Merkezi Profil Bilgilerimi Düzenle
            </a>
            @if($basvurularAcik)
                <a href="{{ route('sso.basvuru') }}" class="btn-secondary" style="margin-bottom: 10px;">
                    🔄 Tekrar Başvuru Yap
                </a>
            @endif
        @elseif($state === 'applications_closed')
            <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Sistem yöneticisi başvuruları açtığında Merkezi Portaldaki "Başvuru Yap" butonu aktif olacaktır.</p>
        @elseif($state === 'pending_role')
            <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Sistem yöneticisi yetki ve rol atamanızı tamamladığında doğrudan giriş yapabileceksiniz.</p>
        @else
            <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Sistem yöneticisinin onay süreci beklenmektedir.</p>
        @endif

        <a href="{{ $centralUrl }}/dashboard" class="btn-secondary">
            ← Merkezi Portala Dön
        </a>
    </div>
</div>

</body>
</html>

