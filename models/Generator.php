<?php

namespace app\models;

use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class Generator extends ActiveRecord
{
    /**
     * @throws Exception
     */
    public function validateCode($code): array
    {
        $ingredientTypes = IngredientType::find()
            ->asArray()
            ->all();
        $inputIngredientsCountByType = [];
        $code = trim($code);
        $ingredientsInArr = str_split($code);

        foreach($ingredientsInArr as $ingredientCode) {
            if ($ingredientCode === ' ') {
                continue;
            }
            if (!array_key_exists($ingredientCode, ArrayHelper::map($ingredientTypes, 'code', 'code'))) {
                throw new Exception("В БД нет ингредиента с кодом '${ingredientCode}'");
            }

            isset($inputIngredientsCountByType[$ingredientCode]) ? $inputIngredientsCountByType[$ingredientCode] += 1 : $inputIngredientsCountByType[$ingredientCode] = 1;
        }

        return $inputIngredientsCountByType;
    }

    public function generateVariants($code): array
    {
        $types = IngredientType::find()
            ->where(['code' => array_keys($code)])
            ->indexBy('id')
            ->asArray()
            ->all();

        $ingredients = Ingredient::find()
            ->where(['type_id' => array_keys($types)])
            ->indexBy('id')
            ->asArray()
            ->all();

        $variantsByIngredientType = [];

        foreach ($types as $typeId => $type) {
            $ingredientsByType = array_filter($ingredients, function ($ingredient) use ($typeId) {
                return $ingredient['type_id'] == $typeId;
            });

            $ingredientIds = array_column($ingredientsByType, 'id');
            $variantsByIngredientType[$typeId] = self::getAllVariantsByCount($ingredientIds, $code[$type['code']]);
        }

        $variants = self::crossJoin($variantsByIngredientType);

        $result = [];

        foreach ($variants as $variant) {
            $element = [
                'products' => [],
                'price' => 0
            ];
            foreach ($variant as $typeId => $ingredient) {
                $ingredientIds = json_decode($ingredient, true);
                foreach ($ingredientIds as $ingredientId) {
                    $element['products'] [] = [
                        'type' => $types[$typeId]['title'],
                        'value' => $ingredients[$ingredientId]['title'],
                    ];
                    $element['price'] += $ingredients[$ingredientId]['price'];
                }
            }
            $result []= $element;
        }

        return $result;
    }
    public static function crossJoin(array $array): array
    {
        $results = [[]];

        foreach ($array as $index => $subArray) {
            $append = [];

            foreach ($results as $product) {
                foreach ($subArray as $item) {
                    $product[$index] = $item;

                    $append[] = $product;
                }
            }

            $results = $append;
        }

        return $results;
    }

    /**
     * @return void
     */
    public static function getVariants(array $array, array $data, int $start, int $end, int $index, array &$result)
    {
        if ($index == count($data)) {
            $result[] = json_encode($data);
        } else {
            if ($start <= $end) {
                $data[$index] = $array[$start];

                self::getVariants($array, $data, $start+1, $end, $index+1, $result);
                self::getVariants($array, $data, $start+1, $end, $index, $result);
            }
        }
    }

    /**
     * @param array $array
     * @param int $size
     * @return array
     */
    public static function getAllVariantsByCount(array $array, int $size): array
    {
        $data = array_fill(0, $size, "");
        $result = [];
        self::getVariants($array, $data,0,sizeof($array) - 1,0,$result);
        return $result;
    }
}