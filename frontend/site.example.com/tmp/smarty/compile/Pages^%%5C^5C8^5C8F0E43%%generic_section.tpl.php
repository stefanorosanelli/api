<?php /* Smarty version 2.6.18, created on 2009-08-04 17:49:52
         compiled from generic_section.tpl */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'default', 'generic_section.tpl', 26, false),array('function', 'assign_associative', 'generic_section.tpl', 34, false),)), $this); ?>
<?php echo ''; ?><?php echo $this->_tpl_vars['view']->element('header'); ?><?php echo '<div class="main"><div class="content-main">'; ?><?php echo $this->_tpl_vars['view']->element('menu'); ?><?php echo ''; ?><?php if (( ! empty ( $this->_tpl_vars['section']['currentContent'] ) )): ?><?php echo ''; ?><?php if ($this->_tpl_vars['section']['currentContent']['object_type'] == 'Gallery'): ?><?php echo ''; ?><?php echo $this->_tpl_vars['view']->element('gallery'); ?><?php echo ''; ?><?php else: ?><?php echo ''; ?><?php if (( ! empty ( $this->_tpl_vars['section']['currentContent']['abstract'] ) ) || ( isset ( $this->_tpl_vars['section']['currentContent']['relations'] ) )): ?><?php echo ''; ?><?php $this->assign('class', 'twocols'); ?><?php echo ''; ?><?php endif; ?><?php echo '<div class="textC '; ?><?php echo ((is_array($_tmp=@$this->_tpl_vars['class'])) ? $this->_run_mod_handler('default', true, $_tmp, '') : smarty_modifier_default($_tmp, '')); ?><?php echo '"><h1>'; ?><?php echo $this->_tpl_vars['section']['currentContent']['title']; ?><?php echo '</h1><h3>'; ?><?php echo $this->_tpl_vars['section']['currentContent']['description']; ?><?php echo '</h3><p class="testo">'; ?><?php echo $this->_tpl_vars['section']['currentContent']['body']; ?><?php echo '</p>'; ?><?php echo smarty_function_assign_associative(array('var' => 'options','object' => $this->_tpl_vars['section']['currentContent'],'showForm' => true), $this);?><?php echo ''; ?><?php echo $this->_tpl_vars['view']->element('show_comments',$this->_tpl_vars['options']); ?><?php echo '</div><div class="abstract '; ?><?php echo ((is_array($_tmp=@$this->_tpl_vars['class'])) ? $this->_run_mod_handler('default', true, $_tmp, '') : smarty_modifier_default($_tmp, '')); ?><?php echo '">'; ?><?php echo ((is_array($_tmp=@$this->_tpl_vars['section']['currentContent']['abstract'])) ? $this->_run_mod_handler('default', true, $_tmp, '') : smarty_modifier_default($_tmp, '')); ?><?php echo ''; ?><?php echo $this->_tpl_vars['view']->element('related'); ?><?php echo '</div>'; ?><?php endif; ?><?php echo '</div>'; ?><?php endif; ?><?php echo '</div>'; ?><?php echo $this->_tpl_vars['view']->element('footer'); ?><?php echo ''; ?>