<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * This file is part of the MailWizz EMA application.
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.3.5
 */
 
?>

<div class="field-row" data-start-index="<?php echo $index;?>" data-field-type="<?php echo $model->type->identifier;?>">
    <?php echo CHtml::hiddenField($model->modelName.'['.$fieldType->identifier.']['.$index.'][field_id]', (int)$model->field_id); ?>

    <ul class="nav nav-tabs">
        <li class="active">
            <a href="javascript:;"><span class="glyphicon glyphicon-th-list"></span> <?php echo Yii::t('list_fields', 'Textarea field');?></a>
        </li>
    </ul>
    
    <div class="panel panel-default no-top-border">
    
        <div class="panel-body">
            
            <div class="form-group col-lg-4">
                <?php echo CHtml::activeLabelEx($model, 'label');?>
                <?php echo CHtml::textField($model->modelName.'['.$fieldType->identifier.']['.$index.'][label]', $model->label, $model->getHtmlOptions('label')); ?>
                <?php echo CHtml::error($model, 'label');?>
            </div>
            <div class="form-group col-lg-2">
                <?php echo CHtml::activeLabelEx($model, 'tag');?>
                <?php echo CHtml::textField($model->modelName.'['.$fieldType->identifier.']['.$index.'][tag]', $model->tag, $model->getHtmlOptions('tag')); ?>
                <?php echo CHtml::error($model, 'tag');?>
            </div>
            <div class="form-group col-lg-2">
                <?php echo CHtml::activeLabelEx($model, 'required');?>
                <?php echo CHtml::dropDownList($model->modelName.'['.$fieldType->identifier.']['.$index.'][required]', $model->required, $model->getRequiredOptionsArray(), $model->getHtmlOptions('required')); ?>
                <?php echo CHtml::error($model, 'required');?>
            </div>
            <div class="form-group col-lg-2">
                <?php echo CHtml::activeLabelEx($model, 'visibility');?>
                <?php echo CHtml::dropDownList($model->modelName.'['.$fieldType->identifier.']['.$index.'][visibility]', $model->visibility, $model->getVisibilityOptionsArray(), $model->getHtmlOptions('visibility')); ?>
                <?php echo CHtml::error($model, 'visibility');?>
            </div>
            <div class="form-group col-lg-2">
                <?php echo CHtml::activeLabelEx($model, 'sort_order');?>
                <?php echo CHtml::dropDownList($model->modelName.'['.$fieldType->identifier.']['.$index.'][sort_order]', $model->sort_order, $model->getSortOrderOptionsArray(), $model->getHtmlOptions('sort_order', array('data-placement' => 'left'))); ?>
                <?php echo CHtml::error($model, 'sort_order');?>
            </div>
            
            <div class="clearfix"><!-- --></div>
            
            <div class="form-group col-lg-6">
                <?php echo CHtml::activeLabelEx($model, 'help_text');?>
                <?php echo CHtml::textField($model->modelName.'['.$fieldType->identifier.']['.$index.'][help_text]', $model->help_text, $model->getHtmlOptions('help_text')); ?>
                <?php echo CHtml::error($model, 'help_text');?>
            </div>
            <div class="form-group col-lg-6">
                <?php echo CHtml::activeLabelEx($model, 'default_value');?>
                <?php echo CHtml::textField($model->modelName.'['.$fieldType->identifier.']['.$index.'][default_value]', $model->default_value, $model->getHtmlOptions('default_value')); ?>
                <?php echo CHtml::error($model, 'default_value');?>
            </div>

        </div>

        <div class="panel-footer">
            <div class="pull-right">
                <a href="javascript:;" class="btn btn-danger btn-xs btn-remove-textarea-field" data-field-id="<?php echo (int)$model->field_id;?>" data-message="<?php echo Yii::t('list_fields', 'Are you sure you want to remove this field? There is no coming back from this after you save the changes.');?>"><?php echo Yii::t('app', 'Remove');?></a>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>    

    </div>

</div>