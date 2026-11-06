<section>
    <header class="flex items-center justify-between pb-4 border-b border-gray-100">
        <div>
            <h2 class="text-lg font-bold text-gray-900">
                {{ __('Şifre ve Güvenlik Yönetimi') }}
            </h2>
            <p class="mt-1 text-xs text-gray-500">
                {{ __('Hesap güvenliği ve şifre güncellemeleri Merkezi Yönetim Sistemi üzerinden yürütülmektedir.') }}
            </p>
        </div>
        <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
            Merkezi Güvenlik
        </span>
    </header>

    <div class="mt-6 space-y-4">
        <div class="p-5 bg-gradient-to-r from-amber-50/80 via-orange-50/40 to-white border border-amber-200/80 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-lg shadow-sm shrink-0 mt-0.5">
                    🔑
                </div>
                <div>
                    <h4 class="text-sm font-bold text-gray-900">Şifre Değişikliği Merkezi Portaldan Yapılır</h4>
                    <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                        Köksan SSO mimarisinde tüm kurumsal uygulamalar için ortak tek şifre kullanılmaktadır. Şifrenizi güncellemek istediğinizde Merkezi Yönetim Portalı profil sayfasından kolayca değiştirebilirsiniz.
                    </p>
                </div>
            </div>

            <a href="{{ $centralUrl ?? 'http://localhost:8001' }}/profile" target="_blank" class="px-5 py-2.5 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-2 whitespace-nowrap shrink-0">
                <span>Merkezi Sistemde Şifremi Değiştir</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
            </a>
        </div>
    </div>
</section>
