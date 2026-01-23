<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

class Client extends Model
{
    use HasFactory;

    /**
     * The projects this client ordered.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * The projects this client ordered.
     */
    public function invoices(): HasManyThrough
    {
        return $this->hasManyThrough(Invoice::class, Project::class);
    }

    /**
     * All address information of this client as one string
     */
    public function fullAddress(): Attribute
    {
        $data = $this->address ? "{$this->address}\n" : '';
        return Attribute::make(fn() => "$data{$this->street}\n{$this->zip} {$this->city}");
    }

    /**
     * Number of hours worked for this client
     */
    public function hours(): Attribute
    {
        $hours = 0;
        foreach ($this->projects as $project) {
            foreach ($project->invoices as $invoice) {
                foreach ($invoice->positions as $position) {
                    $hours += $position->duration;
                }
            }
        }
        return Attribute::make(fn() => $hours);
    }

    /**
     * Net amount earned by this client
     */
    public function net(): Attribute
    {
        $net = 0;
        foreach ($this->projects as $project) {
            foreach ($project->invoices as $invoice) {
                $net += $invoice->net;
            }
        }
        return Attribute::make(fn() => $net);
    }

    /**
     * Number of days this client takse to pay bills on average
     */
    public function avgPaymentDelay(): Attribute
    {
        $days = [];
        foreach ($this->projects as $project) {
            foreach ($project->invoices as $invoice) {
                if ($invoice->invoiced_at && $invoice->paid_at) {
                    $days[] = Carbon::parse($invoice->invoiced_at)->floatDiffInDays($invoice->paid_at);
                }
            }
        }
        return Attribute::make(fn() => count($days) ? array_sum($days)/count($days) : 0);
    }
}
