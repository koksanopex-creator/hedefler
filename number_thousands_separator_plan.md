# Sayısal Alanlara Binlik Ayracı Otomatik Ekleme Planı

Bu geliştirme, `http://localhost:9002/form-entries/create/{id}` ve detay sayfalarında sayısal alanlara girilen değerlerin (örneğin `200000` yazıldığında) anlık ve otomatik olarak görsel binlik ayracıyla (`200.000`) ayrılmasını, alt toplam satırlarında da aynı şekilde binlik ayracıyla formatlanmasını sağlamaktadır.

## Yapılacak Değişiklikler

1. **Giriş Ekranı (`resources/views/form_entries/create.blade.php`):**
   - Sayısal alanların `<input type="number">` yapısı (tarayıcıların binlik ayracını reddetmesi ve çirkin ok butonları koyması sebebiyle) `type="text" inputmode="decimal"` formatına dönüştürülecek.
   - Kullanıcı klavyeden `200000` yazdıkça otomatik olarak binlik noktalarını koyan (`200.000`), imleç konumunu koruyan `handleNumberInput()` ve `formatInputDisplay()` fonksiyonları eklenecek.
   - Backend'e ve veritabanına saf temiz sayıların (`200000` veya `200000.50`) gitmesi için gizli alan (`<input type="hidden">`) entegrasyonu sağlanacak.
   - Sayfa altındaki "Görünen Toplam" ve "Genel Toplam" satırlarında ham `200000.00` yerine binlik ayracına sahip `200.000,00` gösterimi (`formatTotalDisplay()`) sağlanacak.

2. **Detay / Görüntüleme Ekranı (`resources/views/form_entries/show.blade.php`):**
   - Sayısal hücre değerlerinin ve alt toplamların binlik ayracıyla (`200.000` veya `200.000,00`) gösterilmesi (`formatValue()`, `formatTotalDisplay()`).

3. **Backend Güvenliği (`app/Services/FormEntryS.php`):**
   - Kayıt anında herhangi bir sayısal alana formatlı metin gelirse backend tarafında da otomatik temizlenerek (`200.000` -> `200000`) veri tabanı ve analiz bütünlüğü güvenceye alınacak.

4. **Test ve Doğrulama:**
   - Sayısal giriş, otomatik formatlama, formül hesaplama, toplam satırları ve sayfa derlemeleri test edilecek.

