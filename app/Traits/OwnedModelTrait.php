<?php

namespace App\Traits;

use App\Models\Brand;
use App\Models\User;


trait OwnedModelTrait
{

    public function scopeOwnedByBrand($query)
    {
        // brand should be always set by Middleware
        $brand = get_current_brand();

        if ($brand == null) {
            $brand = request()->get('brand');
        }

        if (!$brand) {
            throw new \Exception("No brand set in request");
        }
        return $query->where('brand_id', '=', $brand->id);
    }

    public function scopeOwnedByPartneship($query)
    {
        // brand should be always set by Middleware
        $brand_id = $this->getPartnershipedBrandsId();

        if (!$brand_id) {
            throw new \Exception("No brand partnership set");
        }
        return $query->whereIn('brand_id', $brand_id);
    }

    public function scopeOwnedByBrandOrPartneship($query)
    {
        return $query->where(function ($query) {
            $query->ownedByBrand();
        })->orWhere(function ($query) {
            $query->ownedByPartneship();
        });
    }

    public function scopeOwnedByUser($query)
    {
        // in normal use, this code should be executed with and authed user
        if (!\Auth::user()) {
            throw new \Exception("No user logged in");
        }
        return $query->where('user_id', '=', \Auth::user()->id);
    }

    /**
     * Check if is a element form user autorized brans
     */
    public function scopeAuthorizedBrands($query)
    {
        $user = \Auth::user();
        // in normal use, this code should be executed with and authed user
        if (!$user) {
            throw new \Exception("No user logged in");
        }

        $authorizedBrands = $user->brands()->get()->pluck('id')->toArray();

        return $query->whereIn('brand_id', $authorizedBrands);
    }

    public function checkBrandOwnership()
    {
        $brand = get_current_brand() ? get_current_brand() : request()->get('brand');
        if ($this->brand->id != $brand->id) {
            abort(404, 'This object does not belong to the current brand');
            throw new \Exception("This object does not belong to the current brand");
        }

        // for not returning sensitive data unwilling to
        if (isset($this->brand)) {
            unset($this->brand);
        }

        return $this;
    }
    public function checkBrandPartnership()
    {
        if (is_null($this->getPartnershipedBrandsId()) || !in_array($this->brand->id, $this->getPartnershipedBrandsId())) {
            abort(404, 'This object does not belong to the current brand');
            throw new \Exception("This object does not belong to the current brand");
        }

        // for not returning sensitive data unwilling to
        if (isset($this->brand)) {
            unset($this->brand);
        }

        return $this;
    }

    public function brand()
    {
        if (isset($this->attributes['brand_id']))
            return $this->belongsTo(Brand::class);

        return null;
    }

    public function user()
    {
        if (isset($this->attributes['user_id']))
            return $this->belongsTo(User::class);

        return null;
    }

    private function getPartnershipedBrandsId()
    {
        $brands_id = brand_setting('base.brand.partnershiped_ids', []);

        if (is_string($brands_id))
            $brands_id = explode(",", $brands_id);

        // add id current brand. To show events from current brand not only events from partners
        $brands_id[] = get_current_brand() ? get_current_brand()->id : request()->get('brand')->id;

        return $brands_id;
    }
}
