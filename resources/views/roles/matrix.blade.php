@section('title', 'Yetki Matrisi')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-gray-900 tracking-tight">
                    {{ __('Yetki Matrisi Yönetimi') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Rollere yetki atayarak kullanıcıların erişimlerini kontrol edin</p>
            </div>
            <form action="{{ route('roles.store') }}" method="POST" class="flex items-center gap-2">
                @csrf
                <input type="text" name="name" placeholder="Yeni rol adı (ör: Tasarımcı)" required
                    class="rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3 w-48 sm:w-56">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-semibold text-sm transition shadow-sm whitespace-nowrap">
                    + Rol Ekle
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

            @error('name')
                <div class="p-3 mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg" role="alert">{{ $message }}</div>
            @enderror
            @if(session('success'))
                <div class="p-3 mb-4 text-sm text-teal-800 bg-teal-50 border border-teal-200 rounded-lg flex items-center" role="alert">
                    <svg class="w-5 h-5 mr-2 text-teal-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="p-3 mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg" role="alert">{{ session('error') }}</div>
            @endif

            <form action="{{ route('roles.matrix.update') }}" method="POST">
                @csrf

                {{-- Tablo: Doğrudan sayfa içinde, yapışkan başlık tam navbar altına oturacak şekilde ayarlandı --}}
                <div class="shadow-lg rounded-2xl border border-gray-200 bg-white overflow-visible">
                    <table class="w-full border-separate border-spacing-0 text-sm">
                        <thead class="sticky top-0 lg:top-[56px] z-30">
                            <tr>
                                <th class="sticky top-0 lg:top-[56px] z-30 bg-slate-900 text-white text-left px-4 py-3.5 sm:px-6 sm:py-4 text-xs sm:text-sm font-extrabold uppercase tracking-wider border-b-2 border-r border-slate-700 min-w-[220px] shadow-sm first:rounded-tl-2xl">
                                    Yetki / Rol
                                </th>
                                @foreach($roles as $role)
                                    <th class="sticky top-0 lg:top-[56px] z-30 bg-slate-900 text-white text-center px-2 py-3.5 sm:px-4 sm:py-4 text-[10px] sm:text-xs font-extrabold uppercase tracking-wider border-b-2 border-r border-slate-700 relative group min-w-[120px] shadow-sm {{ $loop->last ? 'rounded-tr-2xl' : '' }}">
                                        <span class="block leading-tight">{{ $role->name }}</span>
                                        <span class="block text-slate-400 font-normal mt-0.5 text-[9px] sm:text-[10px]">{{ $role->slug }}</span>

                                        @if(!in_array($role->slug, ['sistem_yoneticisi', 'yonetici', 'standart_kullanici']))
                                            <button type="button"
                                                onclick="if(confirm('Bu rolü tamamen silmek istediğinize emin misiniz?')) { document.getElementById('delete-role-{{ $role->id }}').submit(); }"
                                                class="absolute top-1 right-1 text-slate-400 hover:text-red-400 opacity-0 group-hover:opacity-100 transition"
                                                title="Rolü Sil">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        @endif
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                        @php
                            $moduleTitles = [
                                'kpi_targets' => 'Dinamik KPI ve Hoshin Kanri Hedefleri',
                                'users' => 'Kullanıcı Yönetimi',
                                'groups' => 'Grup ve Departman Yönetimi',
                                'roles' => 'Rol ve Yetki Matrisi',
                                'form_templates' => 'Form Şablonları ve Alan Tasarımı',
                                'form_schedules' => 'Form Takvimi ve Zamanlama',
                                'form_entries' => 'Form Veri Girişleri ve Onay Süreci',
                                'exports' => 'Dışa Aktarma (Excel Export)',
                                'logs' => 'Sistem ve İşlem Logları',
                                'system' => 'Sistem ve Oturum Yönetimi (Login Takibi)',
                                'dashboard' => 'Dashboard ve Performans Grafikleri',
                            ];
                        @endphp
                        @foreach($permissions as $module => $modulePermissions)
                            @php
                                $displayModuleName = $moduleTitles[$module] ?? mb_convert_case(str_replace('_', ' ', $module), MB_CASE_TITLE, 'UTF-8');
                            @endphp
                            {{-- Modül Başlığı --}}
                            <tr>
                                <td colspan="{{ count($roles) + 1 }}" class="bg-indigo-50/90 px-4 sm:px-6 py-2.5 border-t-2 border-indigo-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 bg-indigo-600 rounded-full inline-block"></span>
                                            <span class="font-black text-indigo-950 uppercase text-xs sm:text-sm tracking-wide">{{ $displayModuleName }}</span>
                                        </div>
                                        <span class="text-[10px] font-mono font-medium text-indigo-600 bg-indigo-100/70 px-2 py-0.5 rounded-md">
                                            {{ $module }}
                                        </span>
                                    </div>
                                </td>
                            </tr>

                            {{-- Yetkiler --}}
                            @foreach($modulePermissions as $permission)
                                <tr class="hover:bg-indigo-50/40 transition-colors border-b border-gray-100">
                                    <td class="px-4 sm:px-6 py-3 border-r border-gray-100">
                                        <div class="font-medium text-gray-900 text-xs sm:text-sm">{{ $permission->name }}</div>
                                        <div class="text-[10px] sm:text-xs text-gray-400 mt-0.5">{{ $permission->slug }}</div>
                                    </td>

                                    @foreach($roles as $role)
                                        @php
                                            $isChecked = in_array($permission->id, $rolePermissions[$role->id] ?? []);
                                            $isSysAdmin = $role->slug === 'sistem_yoneticisi';
                                        @endphp
                                        <td class="px-2 sm:px-4 py-3 text-center border-r border-gray-100">
                                            <input type="checkbox"
                                                name="matrix[{{ $role->id }}][]"
                                                value="{{ $permission->id }}"
                                                class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 cursor-pointer transition"
                                                {{ $isChecked ? 'checked' : '' }}
                                                {{ $isSysAdmin ? 'onclick="return false;" readonly' : '' }}>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
                </div>

                {{-- Kaydet Alanı: Sayfanın altına yapışık --}}
                <div class="sticky bottom-0 z-10 mt-0 bg-white/95 backdrop-blur-sm border-t border-gray-200 shadow-[0_-4px_12px_rgba(0,0,0,0.08)] rounded-b-lg px-4 sm:px-6 py-4 flex flex-col sm:flex-row justify-between items-center gap-3">
                    <div class="text-xs sm:text-sm text-gray-500 text-center sm:text-left">
                        <strong>Not:</strong> Sistem Yöneticisi rolünün yetkileri kilitlidir ve değiştirilemez.
                    </div>
                    <button type="submit" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg shadow-md hover:shadow-lg transition-all text-sm">
                        Matrisi Kaydet
                    </button>
                </div>
            </form>

            {{-- Gizli Silme Formları --}}
            @foreach($roles as $role)
                @if(!in_array($role->slug, ['sistem_yoneticisi', 'yonetici', 'standart_kullanici']))
                    <form id="delete-role-{{ $role->id }}" action="{{ route('roles.destroy', $role) }}" method="POST" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            @endforeach

        </div>
    </div>
</x-app-layout>

