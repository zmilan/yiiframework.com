<?php

use app\models\WikiRevision;
use Neos\Diff\Renderer\Html\HtmlInlineRenderer;
use yii\helpers\Html;

/** @var $model \app\models\Wiki */
/** @var $left WikiRevision */
/** @var $right WikiRevision */


$this->title = 'Wiki - ' . $model->title . ' - Compare Revisions';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="guide-header-wrap">
    <div class="container guide-header common-heading">
        <div class="row">
            <div class="col-md-12">
                <h1 class="guide-headline"><?= Html::encode($this->title) ?></h1>
            </div>
        </div>
    </div>
</div>


<div class="container guide-view lang-en" xmlns="http://www.w3.org/1999/xhtml">
    <div class="row">
        <div class="col-sm-3 col-md-2 col-lg-2">
            <?= $this->render('_sidebar') ?>
        </div>

        <div class="col-sm-9 col-md-10 col-lg-10" role="main">

            <!-- delete from here -->
            <div class="row">
                <div class="col-md-12 col-lg-9">
                    <div class="content wiki-row">
                        <h2 class="title">
                            Difference between
                            #<?= Html::a(Html::encode($left->revision), ['wiki/view', 'id' => $model->id, 'name' => $model->slug, 'revision' => $left->revision]) ?>
                            and
                            #<?= Html::a(Html::encode($right->revision), ['wiki/view', 'id' => $model->id, 'name' => $model->slug, 'revision' => $right->revision]) ?>
                            of<br>
                            <?= Html::a(Html::encode($model->title), ['wiki/view', 'id' => $model->id, 'name' => $model->slug]) ?>
                        </h2>

                        <?php if ($left->equals($right)): ?>
                            You can not compare a revision with itself!
                        <?php else: ?>
                            <?php if ($diffSingle): ?>
                                <p>
                                    Revision #<?= $diffSingle->revision ?>
                                    has been created by <?= $diffSingle->updater->rankLink ?>
                                    on <?= Yii::$app->formatter->asDateTime($diffSingle->updated_at) ?> with the memo:
                                </p>
                                <div class="memo">
                                    <?= $right->memo ?>
                                </div>

                                <?php if ($previous = $diffSingle->findPrevious()): ?>
                                    <?= Html::a(
                                        '&laquo; previous (#' . $previous->revision . ')',
                                        ['wiki/revision', 'id' => $previous->wiki_id, 'r1' => $previous->revision],
                                        ['class' => 'prev-revision']
                                    ) ?>
                                <?php endif; ?>
                                <?php if ($next = $diffSingle->findNext()): ?>
                                    <?= Html::a(
                                        'next (#' . $next->revision . ') &raquo;',
                                        ['wiki/revision', 'id' => $next->wiki_id, 'r1' => $next->revision],
                                        ['class' => 'next-revision']
                                    ) ?>
                                <?php endif; ?>
                            <?php endif; ?>


                            <h3>Changes</h3>

                            <?php $diff = WikiRevision::diff($left, $right, 'title'); ?>
                            <h4>
                                Title
                                <small><?php echo count($diff) <= 1 ? '<span class="label unchanged">unchanged</span>' : '<span class="label changed">changed</span>'; ?></small>
                            </h4>
                            <div class="diff">
                                <?= trim(WikiRevision::diffPrettyHtml($diff)) ?>
                            </div>

                            <?php $diff = WikiRevision::diff($left, $right, 'category.name'); ?>
                            <h4>
                                Category
                                <small><?php echo count($diff) <= 1 ? '<span class="label unchanged">unchanged</span>' : '<span class="label changed">changed</span>'; ?></small>
                            </h4>
                            <div class="diff">
                                <?= trim(WikiRevision::diffPrettyHtml($diff)) ?>
                            </div>

                            <?php $diff = WikiRevision::diff($left, $right, 'tagNames'); ?>
                            <h4>
                                Tags
                                <small><?php echo count($diff) <= 1 ? '<span class="label unchanged">unchanged</span>' : '<span class="label changed">changed</span>'; ?></small>
                            </h4>
                            <div class="diff">
                                <?= trim(WikiRevision::diffPrettyHtml($diff)) ?>
                            </div>

                            <?php $diff = WikiRevision::diff($left, $right, 'content'); ?>
                            <h4>
                                Content
                                <small><?php echo count($diff) <= 1 ? '<span class="label unchanged">unchanged</span>' : '<span class="label changed">changed</span>'; ?></small>
                            </h4>
                            <div class="diff">
                                <?= trim(WikiRevision::diffPrettyHtml($diff)) ?>
                            </div>


                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-12 col-lg-3">
                    <?= $this->render('_metadata.php', [
                        'model' => $model,
                        'extended' => true
                    ]) ?>

                    <?= Html::a('Update Article', ['wiki/update', 'id' => $model->id])?>


                    <div class="revert">
                        <?php echo Html::a('Revert to #'.$left->revision, array('wiki/update','id'=>$model->id,'revision'=>$left->revision)); ?> |
                        <?php echo Html::a('Revert to #'.$right->revision, array('wiki/update','id'=>$model->id,'revision'=>$right->revision)); ?>
                    </div>
                    <h3>Revisions</h3>

                    <?= $this->render('_revisions.php', ['model' => $model]) ?>
                </div>
            </div>


        </div>
    </div>
</div>
