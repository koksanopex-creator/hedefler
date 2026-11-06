@section('title', 'Veri Girişleri (Formlar)')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-gray-900 tracking-tight">
                    {{ __('Veri Girişleri') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Doldurduğunuz veya yetkiniz dahilindeki tüm form kayıtları</p>
            </div>
            <a href="{{ route('form-entries.select-template') }}" class="group relative flex items-center justify-center py-2.5 px-5 border border-transparent text-sm font-semibold rounded-xl text-white bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-md hover:shadow-lg transition-all overflow-hidden">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Yeni Veri Girişi
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                
                @if(session('success'))
                    <div class="p-4 mx-6 mt-6 mb-2 text-sm text-teal-800 bg-teal-50 border border-teal-200 rounded-xl flex items-center" role="alert">
                        <svg class="w-5 h-5 mr-2 text-teal-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        {{ session('success') }}
                    </div>
                @endif

                @php
                    $curStatus = request('status');
                    $curFilterDate = request('filter_date');
                @endphp

                <!-- Filtre Tabları & Durum Çubuğu -->
                <div class="p-4 sm:p-5 border-b border-gray-100 bg-slate-50/60 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Tümü --}}
                        <a href="{{ route('form-entries.index') }}" 
                           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all shadow-xs {{ (empty($curStatus) && empty($curFilterDate)) ? 'bg-white text-indigo-700 shadow-sm border border-indigo-200 ring-2 ring-indigo-500/10' : 'bg-white/80 text-gray-600 hover:bg-white hover:text-gray-900 border border-gray-200/80' }}">
                            <span>Tümü</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold {{ (empty($curStatus) && empty($curFilterDate)) ? 'bg-indigo-50 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $statusCounts['all'] ?? $entries->total() }}
                            </span>
                        </a>

                        {{-- Onay Bekleyenler --}}
                        <a href="{{ route('form-entries.index', ['status' => 'submitted']) }}" 
                           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all shadow-xs {{ $curStatus === 'submitted' ? 'bg-amber-500 text-white shadow-md shadow-amber-500/20 ring-2 ring-amber-400' : 'bg-white text-amber-700 hover:bg-amber-50 border border-amber-200' }}">
                            @if(($statusCounts['submitted'] ?? 0) > 0)
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $curStatus === 'submitted' ? 'bg-white' : 'bg-amber-400' }} opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 {{ $curStatus === 'submitted' ? 'bg-white' : 'bg-amber-500' }}"></span>
                                </span>
                            @endif
                            <span>Onay Bekleyenler</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold {{ $curStatus === 'submitted' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-800' }}">
                                {{ $statusCounts['submitted'] ?? 0 }}
                            </span>
                        </a>

                        {{-- Düzenleme Bekleyenler --}}
                        <a href="{{ route('form-entries.index', ['status' => 'revision_pending']) }}" 
                           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all shadow-xs {{ $curStatus === 'revision_pending' ? 'bg-amber-600 text-white shadow-md shadow-amber-600/20 ring-2 ring-amber-400' : 'bg-white text-amber-800 hover:bg-amber-50 border border-amber-300' }}">
                            @if(($statusCounts['revision_pending'] ?? 0) > 0)
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $curStatus === 'revision_pending' ? 'bg-white' : 'bg-amber-400' }} opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 {{ $curStatus === 'revision_pending' ? 'bg-white' : 'bg-amber-500' }}"></span>
                                </span>
                            @endif
                            <span>Düzenleme Bekleyenler</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold {{ $curStatus === 'revision_pending' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-900' }}">
                                {{ $statusCounts['revision_pending'] ?? 0 }}
                            </span>
                        </a>

                        {{-- Onaylananlar --}}
                        <a href="{{ route('form-entries.index', ['status' => 'approved']) }}" 
                           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all shadow-xs {{ $curStatus === 'approved' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20 ring-2 ring-emerald-400' : 'bg-white text-emerald-700 hover:bg-emerald-50 border border-emerald-200' }}">
                            <span>Onaylananlar</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold {{ $curStatus === 'approved' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ $statusCounts['approved'] ?? 0 }}
                            </span>
                        </a>

                        {{-- Taslaklar --}}
                        <a href="{{ route('form-entries.index', ['status' => 'draft']) }}" 
                           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all shadow-xs {{ $curStatus === 'draft' ? 'bg-slate-700 text-white shadow-md ring-2 ring-slate-400' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' }}">
                            <span>Taslaklar</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold {{ $curStatus === 'draft' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">
                                {{ $statusCounts['draft'] ?? 0 }}
                            </span>
                        </a>

                        {{-- Reddedilenler --}}
                        <a href="{{ route('form-entries.index', ['status' => 'rejected']) }}" 
                           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all shadow-xs {{ $curStatus === 'rejected' ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20 ring-2 ring-rose-400' : 'bg-white text-rose-700 hover:bg-rose-50 border border-rose-200' }}">
                            <span>Reddedilenler</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold {{ $curStatus === 'rejected' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-800' }}">
                                {{ $statusCounts['rejected'] ?? 0 }}
                            </span>
                        </a>
                    </div>

                    @if(!empty($curStatus) || !empty($curFilterDate))
                        <div class="flex items-center gap-2">
                            @if($curFilterDate === 'today')
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-100 text-purple-700 border border-purple-200">
                                    📅 Bugünkü Girişler
                                </span>
                            @endif
                            <a href="{{ route('form-entries.index') }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-white transition-colors border border-dashed border-slate-300">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span>Filtreyi Temizle</span>
                            </a>
                        </div>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tarih</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kullanıcı</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Şablon</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Durum</th>
                                @if(isset($canViewApprover) && $canViewApprover)
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Onaylayan</th>
                                @endif
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse($entries as $entry)
                                <tr class="hover:bg-indigo-50/30 transition-colors group">
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="flex items-center text-sm text-gray-900 font-medium">
                                            <svg class="w-4 h-4 mr-2 text-gray-400 group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            {{ $entry->entry_date->format('d M Y') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-9 w-9 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-full flex items-center justify-center text-indigo-700 font-bold border border-indigo-200 shadow-sm">
                                                {{ mb_substr($entry->user->name, 0, 1) }}
                                            </div>
                                            <div class="ml-3">
                                                <a href="{{ route('users.profile', $entry->user->id) }}" class="text-sm font-bold text-gray-900 hover:text-indigo-600 hover:underline transition-colors">{{ $entry->user->name }}</a>
                                                <p class="text-xs text-gray-500 flex items-center mt-0.5">
                                                    @if($entry->user->group)
                                                        <svg class="w-3 h-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                                        {{ $entry->user->group->name }}
                                                    @else
                                                        Grup Yok
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 inline-block">
                                            {{ $entry->template->name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        @if($entry->revisionRequests->firstWhere('status', 'pending'))
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-300 shadow-xs" title="Düzenleme (revizyon) talebi değerlendirme aşamasında">
                                                <span class="w-1.5 h-1.5 bg-amber-500 rounded-full mr-1.5 animate-pulse"></span>
                                                Düzenleme Talebi Bekliyor
                                            </span>
                                        @elseif($entry->status === 'draft')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                                <span class="w-1.5 h-1.5 bg-gray-400 rounded-full mr-1.5"></span>
                                                Taslak
                                            </span>
                                        @elseif($entry->status === 'submitted')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                <span class="w-1.5 h-1.5 bg-amber-500 rounded-full mr-1.5 animate-pulse"></span>
                                                Onay Bekliyor
                                            </span>
                                        @elseif($entry->status === 'approved')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5"></span>
                                                Onaylandı
                                            </span>
                                        @elseif($entry->status === 'rejected')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200 cursor-help" title="{{ $entry->rejection_reason }}">
                                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-1.5"></span>
                                                Reddedildi
                                            </span>
                                        @endif
                                    </td>
                                    @if(isset($canViewApprover) && $canViewApprover)
                                        <td class="px-6 py-5 whitespace-nowrap text-sm text-gray-500">
                                            @if($entry->approver)
                                                <div class="flex items-center text-emerald-600 font-medium">
                                                    <svg class="w-4 h-4 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    <a href="{{ route('users.profile', $entry->approver->id) }}" class="hover:underline hover:text-emerald-800 transition-colors">
                                                        {{ $entry->approver->name }}
                                                    </a>
                                                </div>
                                            @elseif($entry->status === 'rejected' && $entry->approver)
                                                <div class="flex items-center text-red-600 font-medium">
                                                    <svg class="w-4 h-4 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    <a href="{{ route('users.profile', $entry->approver->id) }}" class="hover:underline hover:text-red-800 transition-colors">
                                                        {{ $entry->approver->name }}
                                                    </a>
                                                </div>
                                            @else
                                                <span class="text-gray-400 italic">-</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-6 py-5 whitespace-nowrap text-right text-sm font-medium flex justify-end gap-2">
                                        <a href="{{ route('form-entries.show', $entry) }}" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-indigo-600 hover:text-indigo-900 hover:bg-indigo-50 hover:border-indigo-200 transition-all shadow-sm">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            İncele
                                        </a>
                                        
                                        @if(app(\App\Services\PermissionS::class)->hasPermission(Auth::user(), 'form_entries.delete'))
                                        <div x-data="{ open: false }" class="inline-block">
                                            <button @click="open = true" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-red-600 hover:text-red-900 hover:bg-red-50 hover:border-red-200 transition-all shadow-sm">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                Sil
                                            </button>

                                            <!-- Modal -->
                                            <div x-show="open" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                                    <div x-show="open" @click="open = false" x-transition.opacity class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                                                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                                    <div x-show="open" x-transition class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                            <div class="sm:flex sm:items-start">
                                                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                                                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                    </svg>
                                                                </div>
                                                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Kayıt Silinecek</h3>
                                                                    <div class="mt-2">
                                                                        <p class="text-sm text-gray-500">Bu form kaydını silmek istediğinize emin misiniz? Bu işlem geri alınamaz.</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse" x-data="{ deleting: false }">
                                                            <form action="{{ route('form-entries.destroy', $entry) }}" method="POST"
                                                                @submit="if(deleting) { $event.preventDefault(); return false; } deleting = true;">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                    :disabled="deleting"
                                                                    :class="deleting ? 'opacity-70 cursor-not-allowed pointer-events-none' : ''"
                                                                    class="w-full inline-flex items-center justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition gap-1.5 cursor-pointer">
                                                                    <svg x-show="deleting" class="animate-spin -ml-1 mr-1.5 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                                    </svg>
                                                                    <span x-text="deleting ? 'Siliniyor...' : 'Evet, Sil'">Evet, Sil</span>
                                                                </button>
                                                            </form>
                                                            <button type="button" @click="if(!deleting) open = false" :disabled="deleting"
                                                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                                                                İptal
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 whitespace-nowrap text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="bg-gray-50 rounded-full p-4 mb-3">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            </div>
                                            <p class="text-gray-500 font-medium">Bu kriterde listelenecek veri girişi bulunamadı.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($entries->hasPages())
                    <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                        {{ $entries->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
