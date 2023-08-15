<?php

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class Ingredient extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName(): string
    {
        return 'ingredient';
    }

    public function getIngredientType(): ActiveQuery
    {
        return $this->hasOne(IngredientType::class, ['id' => 'type_id']);
    }
}