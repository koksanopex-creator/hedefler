<section>
    <header class="flex items-center justify-between pb-4 border-b border-gray-100">
        <div>
            <h2 class="text-lg font-bold text-gray-900">
                {{ __('Profil ve Kimlik Bilgileri') }}
            </h2>
            <p class="mt-1 text-xs text-gray-500">
                {{ __('Profil resminizi güncelleyebilir ve kurumsal bilgilerinizi inceleyebilirsiniz.') }}
            </p>
        </div>
        <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
            Merkezi SSO Entegre
        </span>
    </header>

    @if (session('status') === 'profile-updated')
        <div class="mt-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
            Profil bilgileriniz başarıyla güncellendi.
        </div>
    @elseif (session('status') === 'avatar-removed')
        <div class="mt-4 p-4 rounded-xl bg-slate-100 border border-slate-200 text-slate-800 text-xs font-semibold flex items-center gap-2">
            Profil resminiz kaldırıldı.
        </div>
    @endif

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <!-- Profil Resmi Yükleme Alanı -->
        <div class="p-5 bg-slate-50/70 border border-slate-200/80 rounded-2xl flex flex-col sm:flex-row items-center gap-5">
            <div class="relative shrink-0">
                @if($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar))
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-20 h-20 rounded-2xl object-cover border-2 border-white shadow-md">
                @else
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white text-2xl font-black shadow-md">
                        {{ mb_substr($user->name, 0, 1) }}
                    </div>
                @endif
            </div>

            <div class="flex-1 text-center sm:text-left space-y-2">
                <label class="block text-xs font-bold text-gray-800">Profil Fotoğrafı</label>
                <p class="text-[11px] text-gray-500">JPG, PNG, WEBP formatında maksimum 2 MB fotoğraf yükleyebilirsiniz.</p>
                
                <div class="flex flex-wrap items-center gap-3 pt-1 justify-center sm:justify-start">
                    <label class="cursor-pointer px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-xs transition-all">
                        <span>Fotoğraf Seç</span>
                        <input type="file" name="avatar" class="hidden" accept="image/*" onchange="this.form.submit()">
                    </label>

                    @if($user->avatar)
                        <button type="submit" name="remove_avatar" value="1" class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-semibold transition-all">
                            Fotoğrafı Kaldır
                        </button>
                    @endif
                </div>
                <x-input-error class="mt-2 text-xs" :messages="$errors->get('avatar')" />
            </div>
        </div>

        <!-- Salt Okunur Kurumsal Kimlik Bilgileri -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Ad Soyad</label>
                <div class="relative">
                    <input type="text" value="{{ $user->name }}" readonly class="w-full bg-slate-100 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-700 font-semibold cursor-not-allowed">
                    <span class="absolute right-3 top-2.5 text-gray-400 text-xs" title="Merkezi API tarafından yönetilir">🔒</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">E-posta Adresi</label>
                <div class="relative">
                    <input type="text" value="{{ $user->email }}" readonly class="w-full bg-slate-100 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-700 font-semibold cursor-not-allowed">
                    <span class="absolute right-3 top-2.5 text-gray-400 text-xs" title="Merkezi API tarafından yönetilir">🔒</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Merkezi Departman / Bölüm</label>
                <input type="text" value="{{ $user->central_department ?? 'Tanımlı Değil' }}" readonly class="w-full bg-slate-100 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-700 font-semibold cursor-not-allowed">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Sistem Rolü</label>
                <input type="text" value="{{ $user->role?->name ?? 'Rol Tanımlanmamış' }}" readonly class="w-full bg-slate-100 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-700 font-semibold cursor-not-allowed">
            </div>
        </div>

        <!-- Bilgilendirme Notu -->
        <div class="p-4 bg-indigo-50/70 border border-indigo-100 rounded-xl flex items-start gap-3">
            <span class="text-base">💡</span>
            <div class="text-xs text-indigo-900 leading-relaxed">
                Ad, soyad, e-posta ve departman bilgileriniz Merkezi Yönetim Portalı üzerinden otomatik güncellenmektedir. Bu bilgilerinizde bir değişiklik yapmak isterseniz Merkezi Portal profil sayfanızı kullanabilirsiniz.
            </div>
        </div>
    </form>
</section>
