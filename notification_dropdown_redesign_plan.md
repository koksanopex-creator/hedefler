# Bildirim Açılır Penceresi Modernizasyon Planı

## 1. Amaç ve Kullanıcı Talebi
Kullanıcımızın talebi:
> *"bildirim penceresinide içeirklerin birbirinden ayrılacka şekilde ayarlmısın. daha profesyonel bir şekilde. Birde en fazla 3 bildirim görünsün gerisi için scrrol ile kaydırayım toplamda da 5 tane görünsün. fazlası için tümünü gör butonu ile yönlendirirsin. Birde her bildirim yanında okundu olarka işaretle butonu koyalım. okunanlarıda bu listeden kaldıralım."*

Bu talep doğrultusunda:
1. **İçerik Ayrımı ve Profesyonel Tasarım:** Bildirim penceresindeki maddeler düz ve bitişik metinler yerine bağımsız, gölgeli ve kart bazlı (`card-based`) modern bir görünüme kavuşturulacaktır. Vurgu rengi, bildirim ikonu, zaman bilgisi ve aksiyon butonları net bir görsel hiyerarşi ile konumlandırılacaktır.
2. **Kapasite ve Kaydırma (Scroll) Alanı:**
   - Açılır menüde **sadece okunmamış (unread)** bildirimler listelenecektir.
   - Listede en fazla **5 bildirim** (`limit(5)`) yer alacaktır.
   - Liste kapsayıcısının yüksekliği (`max-h-[265px]`) yaklaşık **3 bildirim** sığacak şekilde ayarlanacak, 4. ve 5. bildirimler için kaydırma çubuğu (scroll) devreye girecektir.
   - 5'ten fazla bildirim veya bildirim geçmişi için alt kısımda *"Tüm Bildirimleri Gör"* butonu yer alacaktır.
3. **Tekil Okundu Olarak İşaretle Butonu ve Listeden Kaldırma:**
   - Her bildirim kartının yanında *"Okundu Olarak İşaretle"* butonu (onay/tik ikonu) yer alacaktır.
   - Tıklandığında bildirim arka planda (AJAX/Fetch) okundu olarak işaretlenecek ve açılır listeden anında kaldırılarak zil üzerindeki sayaç (`badge`) güncellenecektir.
   - Sayfa yenilemeli standart form gönderimi de yedek olarak korunacaktır.
   - Okunan bildirimler bu listeden tamamen çıkarılacaktır.

---

## 2. Mimari ve Etkilenen Bileşenler

### A. Controller Katmanı ([`app/Http/Controllers/NotificationC.php`](file:///c:/Users/celal.karaman/Desktop/HEDEFLER/app/Http/Controllers/NotificationC.php))
- `markAsRead` ve `markAllAsRead` metodlarına `$request->wantsJson() || $request->ajax()` kontrolü eklenerek JSON yanıt desteği sağlanacaktır. Böylece arayüz yenilenmeden anlık bildirim silme/güncelleme imkanı sağlanacaktır.

### B. Görünüm (View) Katmanı ([`resources/views/layouts/navigation.blade.php`](file:///c:/Users/celal.karaman/Desktop/HEDEFLER/resources/views/layouts/navigation.blade.php))
- Zildeki dropdown genişliği ve tasarımı profesyonel hale getirilecektir (`w-[24rem] sm:w-[26rem]`).
- Bildirim listesi sorgusu `Auth::user()->notifications()->limit(10)->get()` yerine `Auth::user()->unreadNotifications()->limit(5)->get()` olarak güncellenecektir.
- Kapsayıcı `max-h-[265px] overflow-y-auto` yapılarak 3 bildirim görünürlüğü ve scroll özelliği sağlanacaktır.
- Her bildirim için:
  - Kart ayrımı,
  - İkon ve başlık/özet alanları,
  - "İlgili Kayda Git" butonu,
  - Anında çalışan "Okundu İşaretle" butonu.
- Bildirim kalmadığında modern boş durum (`empty state`) görseli.

---

## 3. Doğrulama ve Test Adımları
1. Controller ve Blade dosyalarının güncellenmesi.
2. View önbelleğinin temizlenmesi (`php artisan view:clear`).
3. Tarayıcı / HTTP istekleri ile bildirim panelinin render edilmesi ve `markAsRead` JSON API'sinin test edilmesi.
4. Özet dokümanının (`notification_dropdown_redesign_walkthrough.md`) hazırlanması.

