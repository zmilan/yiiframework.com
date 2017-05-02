<?php

namespace app\models;

use app\components\SluggableBehavior;
use Composer\Spdx\SpdxLicenses;
use dosamigos\taggable\Taggable;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "{{%extension}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $tagline
 * @property integer $category_id
 * @property integer $license_id
 * @property integer $from_packagist
 * @property integer $packagist_url
 * @property integer $owner_id
 * @property string $created_at
 * @property string $updated_at
 * @property integer $total_votes
 * @property integer $up_votes
 * @property double $rating
 * @property integer $featured
 * @property integer $comment_count
 * @property integer $download_count
 * @property string $yii_version
 * @property integer $status
 * @property string $description
 *
 * @property User $owner
 * @property ExtensionCategory $category
 */
class Extension extends \yii\db\ActiveRecord
{
    const STATUS_DRAFT = 1;
    const STATUS_PENDING_APPROVAL = 2;
    const STATUS_PUBLISHED = 3;
    const STATUS_DELETED = 5;

    const NAME_PATTERN = '[a-z][a-z0-9\-]*';

    /**
     * object type used for wiki comments
     */
    const COMMENT_TYPE = 'extension';

    /**
     * @var string editor note on upate
     */
    public $memo;


    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => 'created_at', // do not set updated_at on insert
                    self::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
            ],
            'blameable' => [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'owner_id', // TODO owner is must have and should not be changed
                'updatedByAttribute' => false, // TODO owner is must have and should not be changed
            ],
            'tagable' => [
                'class' => Taggable::className(),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%extension}}';
    }

    public function initDefaults()
    {
        $this->description = <<<'MARKDOWN'

...overview of the extension...

## Requirements

...requirements of using this extension (e.g. Yii 2.0 or above)...

## Installation

...how to install the extension (e.g. composer install extensionname)...

## Usage

...how to use this extension...

...can use code blocks like the following...

```php
$model=new User;
$model->save();
```

## Resources

**DELETE THIS SECTION IF YOU DO NOT HAVE IT**

...external resources for this extension...

 * [Project page](URL to your project page)
 * [Try out a demo](URL to your project demo page)

MARKDOWN;

        $this->license_id = 'BSD-3-Clause';

    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'packagist_url', 'tagline', 'description'], 'filter', 'filter' => 'trim'],

            [['name', 'packagist_url', 'category_id', 'yii_version', 'license_id', 'tagline', 'description'], 'required'],

            ['name', 'match', 'pattern' => '/^' . self::NAME_PATTERN . '$/'],
            ['name', 'string', 'min' => 3, 'max' => 32],
            ['name', 'unique'],

            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => ExtensionCategory::className(), 'targetAttribute' => ['category_id' => 'id']],
            ['license_id', 'validateLicenseId'], // spdx

            ['packagist_url', 'validatePackagistUrl'],

            [['description'], 'string'],
            [['tagline'], 'string', 'max' => 128],

            [['yii_version'], 'string', 'max' => 32],

            [['tagNames'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return [
            'create_packagist' => ['packagist_url', 'category_id', 'tagNames'],
            'create_custom' => ['name', 'category_id', 'yii_version', 'license_id', 'tagline', 'description', 'tagNames'],
            'update_packagist' => ['category_id', 'tagNames'],
            'update_custom' => ['category_id', 'yii_version', 'license_id', 'tagline', 'description', 'tagNames'],

        ];
    }

    public function validatePackagistUrl($attribute)
    {
        if (!is_string($this->$attribute)) {
            $this->addError($attribute, 'Packagist URL is invalid.');
            return;
        }
        $url = parse_url($this->$attribute);
    }

    public function validateLicenseId($attribute)
    {
        if (!is_string($this->$attribute)) {
            $this->addError($attribute, 'License must be a valid SPDX License Identifier.');
            return;
        }

        $spdx = new SpdxLicenses();
        if (!$spdx->validate($this->$attribute)) {
            $this->addError($attribute, 'License must be a valid SPDX License Identifier.');
            return;
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'tagline' => 'Tagline',
            'category_id' => 'Category ID',
            'license_id' => 'License ID',
            'owner_id' => 'Owner ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'total_votes' => 'Total Votes',
            'up_votes' => 'Up Votes',
            'rating' => 'Rating',
            'featured' => 'Featured',
            'comment_count' => 'Comment Count',
            'download_count' => 'Download Count',
            'yii_version' => 'Yii Version',
            'status' => 'Status',
            'description' => 'Description',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOwner()
    {
        return $this->hasOne(User::className(), ['id' => 'owner_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(ExtensionCategory::className(), ['id' => 'category_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(ExtensionTag::className(), ['id' => 'extension_tag_id'])
            ->viaTable('extension2extension_tags', ['extension_id' => 'id']);
    }

    /**
     * @inheritdoc
     * @return ExtensionQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ExtensionQuery(get_called_class());
    }

    public function getContentHtml()
    {
        return Yii::$app->formatter->asGuideMarkdown($this->description);
    }

    public static function getLicenseSelect()
    {
        $spdx = new SpdxLicenses();

        $identifiers = [
            'Apache-2.0',
            'EPL-1.0',
            'AGPL-3.0',
            'GPL-2.0',
            'GPL-3.0',
            'LGPL-3.0',
            'MIT',
            'MPL-1.1',
            'MPL-2.0',
            'BSD-2-Clause',
            'BSD-3-Clause',
            'PHP-3.0',
            'Sleepycat',
        ];
        $result = [];
        foreach($identifiers as $i) {
            $license = $spdx->getLicenseByIdentifier($i);
            if ($license) {
                $result[$i] = $license[0];
            }
        }
        asort($result);
        $result['other'] = 'Other Open Source License';
        return $result;
    }
}