<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Product;
use App\Models\OrderDetail;

class Categories extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = 'categories';
    protected $primaryKey = 'ID';
    
    protected $fillable = [
        'Name',
        'Is_active',
        'Parent_id',
        'Image',
        'created_at',
        'updated_at'
    ];

    public function loadTrashed()
    {
        return $this->onlyTrashed()->with('parent')->orderBy('ID', 'desc')->paginate(4);
    }

    public function hasRelatedRecords($id): bool
    {
        $hasProducts = Product::where('categories_id', $id)->exists();
        $hasOrders = OrderDetail::whereHas('product', function ($q) use ($id) {
            $q->where('categories_id', $id);
        })->exists();

        return $hasProducts || $hasOrders;
    }

    public function restoreById($id)
    {
        $item = $this->withTrashed()->find($id);
        if (!$item) return false;
        return $item->restore();
    }

    public function forceDeleteByIdIfNoRelations($id)
    {
        if ($this->hasRelatedRecords($id)) {
            return false;
        }
        $item = $this->withTrashed()->find($id);
        if (!$item) return false;
        return $item->forceDelete();
    }

    // Relationship với danh mục cha
    public function parent()
    {
        return $this->belongsTo(Categories::class, 'Parent_id', 'ID');
    }

    // Relationship với danh mục con
    public function children()
    {
        return $this->hasMany(Categories::class, 'Parent_id', 'ID');
    }

    public function loadAll()
    {
        return $this->with('parent')->orderBy('ID', 'desc')->paginate(4);
    }

    public function loadOneID($id)
    {
        return $this->with('parent')->find($id);
    }

    public function insertData($params)
    {
        return $this->create($params);
    }

    public function updateData($params, $id)
    {
        return $this->find($id)->update($params);
    }

    public function deleteData($id)
    {
        return $this->find($id)->delete();
    }
}
