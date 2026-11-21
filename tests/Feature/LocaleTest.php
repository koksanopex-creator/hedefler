<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use App\Http\Middleware\SetLocaleM;

class LocaleTest extends TestCase
{
    public function test_can_switch_locale_to_english()
    {
        $response = $this->get('/locale/en');

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
    }

    public function test_can_switch_locale_to_turkish()
    {
        $response = $this->get('/locale/tr');

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'tr');
    }

    public function test_invalid_locale_is_ignored()
    {
        $response = $this->get('/locale/fr');

        $response->assertRedirect();
        $response->assertSessionMissing('locale');
    }

    public function test_middleware_sets_locale_correctly()
    {
        Session::put('locale', 'en');

        $middleware = new SetLocaleM();
        $request = Request::create('/test', 'GET');

        $middleware->handle($request, function ($req) {
            $this->assertEquals('en', App::getLocale());
            return response('OK');
        });

        Session::put('locale', 'tr');
        $middleware->handle($request, function ($req) {
            $this->assertEquals('tr', App::getLocale());
            return response('OK');
        });
    }

    public function test_dashboard_translations_render_correctly_in_english()
    {
        App::setLocale('en');

        $this->assertEquals('Welcome', __('Hoş geldiniz'));
        $this->assertEquals('Dashboard & KPI Period Cockpit', __('Dashboard & KPI Dönem Kokpiti'));
        $this->assertEquals('Annual Cumulative', __('Yıllık Kümülatif'));
        $this->assertEquals('Company-Wide Target Achievement', __('Şirket Geneli Hedef Başarısı'));
        $this->assertEquals('Overall Average', __('Genel Ortalama'));
        $this->assertEquals('Active Target', __('Aktif Hedef'));
        $this->assertEquals('Achieved Target', __('Hedefe Ulaşan'));
        $this->assertEquals('Department & Group KPI Achievement Status', __('Departman & Grup KPI Başarı Durumu'));
        $this->assertEquals('Members', __('Üye'));
        $this->assertEquals('Targets', __('Hedef'));
    }
}
