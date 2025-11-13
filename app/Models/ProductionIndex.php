<?php

namespace OGame\Models;

class ProductionIndex
{
    /**
     * Basic income
     *
     * @var Resources
     */
    public Resources $basic;

    /**
     * Mine only, includes solar satellite
     *
     * @var Resources
     */
    public Resources $mine;

    /**
     * Basic + mine + all bonuses
     *
     * @var Resources
     */
    public Resources $total;

    /**
     * Plasma Technology bonus amount
     *
     * @var Resources
     */
    public Resources $plasma_technology;

    /**
     * Planet slot bonus amount
     *
     * @var Resources
     */
    public Resources $planet_slot;

    /**
     * Engineer bonus amount (energy)
     *
     * @var Resources
     */
    public Resources $engineer;

    /**
     * Geologist bonus amount (mine only)
     *
     * @var Resources
     */
    public Resources $geologist;

    /**
     * Commanding Staff bonus amount
     * - additional bonuses when all officers are active
     *
     * @var Resources
     */
    public Resources $commanding_staff;

    /**
     * Active item bonuses
     *
     * @var Resources
     */
    public Resources $items;

    /**
     * Player class bonus amount
     * - Collector: +25% base mine production, +10% base energy production
     *
     * @var Resources
     */
    public Resources $player_class;

    /**
     * Crawler bonus amount
     * - Each crawler provides +0.02% production (0.03% for Collector)
     * - Maximum 50% of base production
     *
     * @var Resources
     */
    public Resources $crawler;

    public function __construct()
    {
        $this->basic = new Resources();
        $this->mine = new Resources();
        $this->total = new Resources();
        $this->plasma_technology = new Resources();
        $this->planet_slot = new Resources();
        $this->engineer = new Resources();
        $this->geologist = new Resources();
        $this->commanding_staff = new Resources();
        $this->items = new Resources();
        $this->player_class = new Resources();
        $this->crawler = new Resources();
    }

    /**
     * Adds the provided ProductionIndex values to this one.
     *
     * @param ProductionIndex $productionIndex
     * @return void
     */
    public function add(ProductionIndex $productionIndex): void
    {
        $this->basic->add($productionIndex->basic);
        $this->mine->add($productionIndex->mine);
        $this->total->add($productionIndex->total);
        $this->plasma_technology->add($productionIndex->plasma_technology);
        $this->planet_slot->add($productionIndex->planet_slot);
        $this->engineer->add($productionIndex->engineer);
        $this->geologist->add($productionIndex->geologist);
        $this->commanding_staff->add($productionIndex->commanding_staff);
        $this->items->add($productionIndex->items);
        $this->player_class->add($productionIndex->player_class);
        $this->crawler->add($productionIndex->crawler);
    }
}
