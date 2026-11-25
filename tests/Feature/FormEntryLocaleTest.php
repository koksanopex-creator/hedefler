<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\App;
use Tests\TestCase;

class FormEntryLocaleTest extends TestCase
{
    public function test_form_entry_translations_render_correctly_in_english()
    {
        App::setLocale('en');

        // Form template and partition
        $this->assertEquals('GOALS TEMPLATE', __('HEDEFLER ŞABLONU'));
        $this->assertEquals('PARTITIONED GOAL ENTRY', __('PARÇALI HEDEF GİRİŞİ'));
        $this->assertEquals('CAP', __('KAPAK'));
        $this->assertEquals('COMMERCIAL PET RESIN', __('TİCARİ PET RESIN'));
        $this->assertEquals('HANDLE', __('KULP'));
        $this->assertEquals('SHEET', __('LEVHA'));

        // Columns & labels
        $this->assertEquals('MATERIAL', __('MALZEME'));
        $this->assertEquals('MATERIAL DESCRIPTION', __('MALZEME TANIMI'));
        $this->assertEquals('ORDERER', __('SİPARİŞ VEREN'));
        $this->assertEquals('COUNTRY', __('ÜLKE'));
        $this->assertEquals('CITY', __('ŞEHİR'));
        $this->assertEquals('ELONGATION', __('UZAMA'));
        $this->assertEquals('INACTIVE', __('PASİF'));
        $this->assertEquals('LOCKED', __('KİLİTLİ'));
        $this->assertEquals('DELETE', __('SİL'));

        // Actions & Buttons
        $this->assertEquals('Add Row', __('Satır Ekle'));
        $this->assertEquals('Clear All', __('Tümünü Temizle'));
        $this->assertEquals('Download Template', __('Şablon İndir'));
        $this->assertEquals("Import from Excel", __('Excel\'den Yükle'));
        $this->assertEquals('Save as Draft', __('Taslak Olarak Kaydet'));
        $this->assertEquals('Submit for Approval', __('Onaya Gönder'));
        $this->assertEquals('Change Department', __('Bölüm Değiştir'));

        // Placeholders & Totals
        $this->assertEquals('Select...', __('Seçiniz...'));
        $this->assertEquals('Select Year...', __('Yıl Seçiniz...'));
        $this->assertEquals('Visible Total:', __('Görünen Toplam:'));
        $this->assertEquals('Grand Total (All):', __('Genel Toplam (Tümü):'));
        $this->assertEquals('VISIBLE TOTAL:', __('GÖRÜNEN TOPLAM:'));
        $this->assertEquals('GRAND TOTAL (ALL):', __('GENEL TOPLAM (TÜMÜ):'));

        // Full entry card on select_partition
        $this->assertEquals('Single Piece / Full Table', __('Tek Parça / Tam Tablo'));
        $this->assertEquals('All', __('Tüm'));
        $this->assertEquals('Values — Full Single-Table Entry', __('Değerleri İçin Tek Parça Giriş'));
        $this->assertEquals('Latest Full Draft', __('Son Tam Taslak'));
        $this->assertEquals('— Start Full Entry', __('İçin Tek Parça Giriş Başlat'));

        // Master Data custom sort mode
        $this->assertEquals('Custom Order', __('Özel Sıra'));
        $this->assertEquals('CUSTOM ORDER ACTIVE', __('ÖZEL SIRA AKTİF'));
        $this->assertEquals('Order', __('Sıra'));
        $this->assertEquals('Sort mode updated.', __('Sıralama modu güncellendi.'));
        $this->assertEquals('Order saved.', __('Sıralama kaydedildi.'));
    }
}
