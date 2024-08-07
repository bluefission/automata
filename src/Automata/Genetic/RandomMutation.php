<?php

class RandomMutation extends Mutation {
    private $_mutationRate;

    public function __construct(float $mutationRate = 0.1) {
        $this->_mutationRate = $mutationRate;
    }

    public function mutate($individual) {
        foreach ($individual->toArray() as $key => $value) {
            if (mt_rand() / mt_getrandmax() < $this->_mutationRate) {
                // Apply a random change to the $value
                // For example, for a numeric value:
                $newValue = $value + (mt_rand() / mt_getrandmax() * 2 - 1);
                $individual->field($key, $newValue);
            }
        }
    }
}