<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

class LocationMapPicker extends Field
{
    protected string $view = 'filament.forms.components.location-map-picker';

    protected float|Closure|null $latitude = null;

    protected float|Closure|null $longitude = null;

    protected int|Closure|null $radius = null;

    protected string|Closure|null $address = null;

    public function latitude(float|Closure|null $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function longitude(float|Closure|null $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function radius(int|Closure|null $radius): static
    {
        $this->radius = $radius;

        return $this;
    }

    public function address(string|Closure|null $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->evaluate($this->latitude);
    }

    public function getLongitude(): ?float
    {
        return $this->evaluate($this->longitude);
    }

    public function getRadius(): ?int
    {
        return $this->evaluate($this->radius);
    }

    public function getAddress(): ?string
    {
        return $this->evaluate($this->address);
    }

    public function getParentStatePath(): string
    {
        $statePath = $this->getStatePath();
        $parts = explode('.', $statePath);
        array_pop($parts);
        return implode('.', $parts);
    }
}
