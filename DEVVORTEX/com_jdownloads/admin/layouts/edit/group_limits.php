<?php

defined('JPATH_BASE') or die;

    $options = $this->options;
    $form = $displayData->getForm();
    
    $group_id = $displayData->group_id;
    
    if ($group_id != 1 && $group_id != 9)
    {
        echo 
        '<div class="row">';
            echo 
            '<div class="col-xl-6">';
            
                echo $form->renderField('download_limit_daily');
                echo $form->renderField('download_limit_daily_msg');
                echo $form->renderField('myspacer');
                echo $form->renderField('download_limit_weekly');
                echo $form->renderField('download_limit_weekly_msg');
                echo $form->renderField('myspacer');
                echo $form->renderField('download_limit_monthly');
                echo $form->renderField('download_limit_monthly_msg');
                echo $form->renderField('myspacer');    
                echo $form->renderField('download_volume_limit_daily');
                echo $form->renderField('download_volume_limit_daily_msg');
                echo $form->renderField('myspacer');    
                echo $form->renderField('download_volume_limit_weekly');
                echo $form->renderField('download_volume_limit_weekly_msg');
                echo $form->renderField('myspacer');    
        
            echo 
            '</div>';
            echo 
            '<div class="col-xl-6">';
        
                echo $form->renderField('download_volume_limit_monthly');
                echo $form->renderField('download_volume_limit_monthly_msg');                    
                echo $form->renderField('myspacer');    
                echo $form->renderField('how_many_times');                    
                echo $form->renderField('how_many_times_msg');                    
                echo $form->renderField('myspacer');    
                echo $form->renderField('upload_limit_daily');                    
                echo $form->renderField('upload_limit_daily_msg');                    
                echo $form->renderField('myspacer');    
    
    }
                
                echo $form->renderField('download_limit_after_this_time');                    
                echo $form->renderField('myspacer');
                echo $form->renderField('transfer_speed_limit_kb');                                        
    
            echo '</div>';
        echo '</div>';
?>