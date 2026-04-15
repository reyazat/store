<?php if($deprecated !== false): ?>
<?php ($text = $deprecated === true ? 'deprecated' : "deprecated:$deprecated"); ?>
<?php $__env->startComponent('scribe::components.badges.base', ['colour' => 'darkgoldenrod', 'text' => $text]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php /**PATH /home/user/ecommerce-2/vendor/knuckleswtf/scribe/resources/views/components/badges/deprecated.blade.php ENDPATH**/ ?>