<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterDataTypeM;
use App\Models\MasterDataItemM;
use App\Models\MasterDataBatchM;
use App\Models\UserM;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MasterDataC extends Controller
{
    public function index()
    {
        // Türkçe alfabetik sıralı tipleri getir; öğeler kendi kategorisinin sort_mode ayarına göre sıralanır
        $driver = DB::connection()->getDriverName();
        $nameOrder = $driver === 'mysql' ? 'name COLLATE utf8mb4_turkish_ci ASC' : 'name ASC';

        $allTypes = MasterDataTypeM::with(['parent', 'items'])
            ->orderByRaw($nameOrder)
            ->get();

        // Hiyerarşik ağaç (DFS) düzleştirme ve derinlik (depth) hesaplama
        $tree = [];
        $buildTree = function($parentId, $depth) use (&$buildTree, $allTypes, &$tree) {
            foreach ($allTypes->where('parent_id', $parentId) as $type) {
                $type->depth = $depth;
                $tree[] = $type;
                $buildTree($type->id, $depth + 1);
            }
        };
        $buildTree(null, 0);
        $types = collect($tree);

        $typesList = $types->map(fn($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'parent_id' => $t->parent_id,
            'sort_mode' => $t->sort_mode ?? 'alphabetical',
        ])->keyBy('id');
        
        $allTypesData = $types->map(function($type) {
            return [
                'id' => $type->id,
                'sort_mode' => $type->sort_mode ?? 'alphabetical',
                'items' => $type->items->map(function($i) { 
                    return [
                        'id' => $i->id, 
                        'name' => $i->name, 
                        'parent_id' => $i->parent_id,
                        'sort_order' => (int) ($i->sort_order ?? 0),
                        'user_id' => $i->user_id
                    ]; 
                })->values()->toArray()
            ];
        })->keyBy('id');

        // Geri alma seçenekleri için son aktif toplu eklemeler
        $recentBatches = MasterDataBatchM::with(['type', 'user'])
            ->where('is_undone', false)
            ->latest()
            ->take(5)
            ->get();

        return view('master_data.index', compact('types', 'typesList', 'allTypesData', 'recentBatches'));
    }

    public function storeType(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:master_data_types,id',
            'sort_mode' => 'nullable|in:alphabetical,custom',
        ]);

        MasterDataTypeM::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name, '_') . '_' . time(),
            'parent_id' => $request->parent_id,
            'sort_mode' => $request->input('sort_mode', 'alphabetical'),
        ]);

        return back()->with('success', 'Master Data Tipi başarıyla eklendi.');
    }

    public function destroyType(MasterDataTypeM $type)
    {
        // Eğer formlarda kullanılıyorsa silinmesini engelleyebiliriz ama şimdilik cascade delete var
        $type->delete();
        return back()->with('success', 'Master Data Tipi başarıyla silindi.');
    }

    public function updateType(Request $request, MasterDataTypeM $type)
    {
        $request->validate([
            'parent_id' => 'nullable|exists:master_data_types,id'
        ]);

        if ($request->parent_id == $type->id) {
            return back()->with('error', 'Bir kategori kendisinin üst kategorisi olamaz.');
        }

        $type->update([
            'parent_id' => $request->parent_id
        ]);

        return back()->with('success', 'Kategori bağı başarıyla güncellendi.')->with('selectedTypeId', $type->id);
    }

    /**
     * Kategorinin sıralama modunu (alphabetical / custom) günceller.
     */
    public function updateSortMode(Request $request, MasterDataTypeM $type)
    {
        $request->validate([
            'sort_mode' => 'required|in:alphabetical,custom',
        ]);

        DB::transaction(function () use ($request, $type) {
            $type->update(['sort_mode' => $request->sort_mode]);

            // İlk kez özel sıralamaya geçiliyorsa henüz numarası olmayan öğelere başlangıç sırası ver
            if ($request->sort_mode === 'custom') {
                $hasAnyOrder = MasterDataItemM::where('type_id', $type->id)
                    ->where('sort_order', '>', 0)
                    ->exists();

                if (!$hasAnyOrder) {
                    $existingItems = MasterDataItemM::where('type_id', $type->id)
                        ->orderBy('id', 'asc')
                        ->get(['id']);

                    foreach ($existingItems as $index => $existingItem) {
                        MasterDataItemM::where('id', $existingItem->id)
                            ->update(['sort_order' => $index + 1]);
                    }
                }
            }
        });

        $type->load('items');
        $freshItems = $type->items->map(fn($i) => [
            'id' => $i->id,
            'name' => $i->name,
            'parent_id' => $i->parent_id,
            'sort_order' => (int) ($i->sort_order ?? 0),
            'user_id' => $i->user_id,
        ])->values()->toArray();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'sort_mode' => $type->sort_mode,
                'items' => $freshItems,
                'message' => __('Sıralama modu güncellendi.'),
            ]);
        }

        return back()->with('success', __('Sıralama modu güncellendi.'))->with('selectedTypeId', $type->id);
    }

    /**
     * Özel sıralı kategoride öğelerin sort_order sırasını topluca kaydeder.
     */
    public function reorderItems(Request $request, MasterDataTypeM $type)
    {
        $request->validate([
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'integer',
        ]);

        $orderedIds = array_values(array_unique(array_map('intval', $request->item_ids)));

        DB::transaction(function () use ($type, $orderedIds) {
            foreach ($orderedIds as $index => $itemId) {
                MasterDataItemM::where('type_id', $type->id)
                    ->where('id', $itemId)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => __('Sıralama kaydedildi.'),
        ]);
    }

    public function storeItem(Request $request)
    {
        $request->validate([
            'type_id' => 'required|exists:master_data_types,id',
            'name' => 'required|string',
            'parent_id' => 'nullable|exists:master_data_items,id'
        ]);

        // Eğer eklendiği tipin bir parent tipi varsa, parent_id zorunlu olmalı ki zincir kırılamasın
        $type = MasterDataTypeM::find($request->type_id);
        if ($type->parent_id && !$request->parent_id) {
            return back()->with('error', 'Bu öğe bir alt tip olduğu için, bir üst öğeye (parent) bağlanmalıdır.');
        }

        // Noktalı virgül veya yeni satır (\n, \r) ile ayır
        $names = preg_split('/[\n\r;]+/', $request->name);
        $createdIds = [];
        $addedNames = [];

        // 1. Çoklu veri eklemede N+1 sorgusunu önlemek için tüm kullanıcıları tek seferde hafızaya al
        $userMap = UserM::select('id', 'name')->get()->keyBy(function($u) {
            return mb_strtolower(trim($u->name));
        });

        // 2. Binlerce satır eklense dahi veri bütünlüğünü ve hızı garantiye almak için DB Transaction
        $maxSortOrder = (int) MasterDataItemM::where('type_id', $request->type_id)->max('sort_order');

        DB::beginTransaction();
        try {
            foreach ($names as $name) {
                $name = trim($name);
                if (!empty($name)) {
                    // İsim sistemdeki bir kullanıcının adıyla eşleşiyorsa otomatik user_id bağla
                    $matchedUser = $userMap->get(mb_strtolower($name));

                    $item = MasterDataItemM::create([
                        'type_id' => $request->type_id,
                        'name' => $name,
                        'parent_id' => $request->parent_id,
                        'sort_order' => ++$maxSortOrder,
                        'user_id' => $matchedUser ? $matchedUser->id : null
                    ]);
                    $createdIds[] = $item->id;
                    $addedNames[] = $name;
                }
            }

            $count = count($createdIds);
            if ($count > 0) {
                $preview = implode(', ', array_slice($addedNames, 0, 4)) . ($count > 4 ? " ... (+" . ($count - 4) . " veri)" : '');
                
                $batch = MasterDataBatchM::create([
                    'user_id' => auth()->id(),
                    'type_id' => $request->type_id,
                    'count' => $count,
                    'item_ids' => $createdIds,
                    'preview' => $preview,
                    'is_undone' => false,
                ]);

                DB::commit();

                $message = $count > 1 ? "$count adet veri başarıyla eklendi." : "Veri başarıyla eklendi.";
                
                return back()->with('success', $message)
                    ->with('last_batch_id', $batch->id)
                    ->with('last_batch_count', $count)
                    ->with('last_batch_type_name', $type->name)
                    ->with('selectedTypeId', $request->type_id);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Veriler kaydedilirken bir hata oluştu: ' . $e->getMessage())->with('selectedTypeId', $request->type_id);
        }

        return back()->with('error', 'Geçerli bir veri girilmedi.')->with('selectedTypeId', $request->type_id);
    }

    public function undoBatch(MasterDataBatchM $batch)
    {
        if ($batch->is_undone) {
            return back()->with('error', 'Bu işlem grubu daha önce zaten geri alınmış.');
        }

        $itemIds = $batch->item_ids ?? [];
        $deletedCount = MasterDataItemM::whereIn('id', $itemIds)->delete();

        $batch->update(['is_undone' => true]);

        $typeName = $batch->type?->name ?? 'Kategori';
        return back()->with('success', "Geri Alma Başarılı: '{$typeName}' kategorisine eklenen son {$deletedCount} adet veri silindi.")
                     ->with('selectedTypeId', $batch->type_id);
    }

    public function destroyItem(MasterDataItemM $item)
    {
        $typeId = $item->type_id;
        $item->delete();
        return back()->with('success', 'Veri başarıyla silindi.')->with('selectedTypeId', $typeId);
    }

    public function updateItem(Request $request, MasterDataItemM $item)
    {
        $request->validate([
            'name' => 'required|string',
            'parent_id' => 'nullable|exists:master_data_items,id'
        ]);

        $item->update([
            'name' => $request->name,
            'parent_id' => $request->parent_id
        ]);

        return back()->with('success', 'Veri başarıyla güncellendi.')->with('selectedTypeId', $item->type_id);
    }

    public function destroyMultipleItems(Request $request)
    {
        $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'exists:master_data_items,id',
            'type_id' => 'required|exists:master_data_types,id'
        ]);

        MasterDataItemM::whereIn('id', $request->item_ids)->delete();

        return back()->with('success', count($request->item_ids) . ' adet veri başarıyla silindi.')->with('selectedTypeId', $request->type_id);
    }

    // API end-point for cascading dropdowns
    public function getItems(Request $request, MasterDataTypeM $type)
    {
        $query = MasterDataItemM::where('type_id', $type->id);
        
        if ($request->has('parent_id') && $request->parent_id != '') {
            $query->where('parent_id', $request->parent_id);
        }

        $items = $query->orderedForType($type)->get(['id', 'name', 'sort_order']);
        
        return response()->json($items);
    }
}
