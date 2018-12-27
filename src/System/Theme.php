<?php
namespace Haitun\Service\TpAdmin\System;

/**
 * 
 * 主题基类
 * 主题和模板里只放控制界面的代码，如数据格式，页面布局，不要有业务代码， 更不要操作数据库 
 *
 */
class Theme
{

	/** @var Template | mixed */
	protected $template = null;
	protected $templateMethods = null;

	/*
	 * @param object $template 模板
	 */
	public function setTemplate($template)
	{
		$this->template = $template;
		$this->templateMethods = get_class_methods($template);
	}

	/**
     * 
     * 输出函数
	 *
     */
    public function display()
    {
		if ($this->template === null) return;
		$template = $this->template;
		$templateMethods = $this->templateMethods;

		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
			<meta name="description" content="<?php echo $template->getMetaDescription();?>" />
			<meta name="keywords" content="<?php echo $template->getMetaKeywords();?>" />
			<title><?php echo $template->getTitle(); ?></title>

			<?php $this->head(); ?>
			<?php if (in_array('head', $templateMethods)) $template->head(); ?>

		</head>
		<body>
			<div class="theme-body-container">
				<div class="theme-body">
				<?php
				if (in_array('body', $templateMethods)) {
					$template->body();
				} else {
					$this->body();
				}
				?>
				</div>
			</div>
		</body>
		</html>
		<?php
    }



    /**
     * 
     * <head></head>头可加 js/css
     */
    protected function head()
    {
    }


	/**
	 *
	 * 主体
	 */
	protected function body()
	{
		$template = $this->template;
		$templateMethods = $this->templateMethods;
		?>
		<div class="theme-north-container">
			<div class="theme-north">
				<?php
				if (in_array('north', $templateMethods)) {
					$template->north();
				} else {
					$this->north();
				}
				?>
			</div>
		</div>

		<div class="theme-middle-container">
			<div class="theme-middle">
				<?php
				if (in_array('middle', $templateMethods)) {
					$template->middle();
				} else {
					$this->middle();
				}
				?>
			</div>
		</div>

		<div class="theme-south-container">
			<div class="theme-south">
				<?php
				if (in_array('south', $templateMethods)) {
					$template->south();
				} else {
					$this->south();
				}
				?>
			</div>
		</div>
		<?php
	}

    /**
     * 
     * 项部
     */
    protected function north()
    {
    }


	protected function middle()
	{
		$template = $this->template;
		$templateMethods = $this->templateMethods;

		$west = in_array('west', $templateMethods);
		$east = in_array('east', $templateMethods);

		$westWidth = 25;
		$centerWidth = 50;
		$eastWidth = 25;

		if (!$west || !$east) {
			if (!$west && !$east) {
				$centerWidth = 100;
			} else {
				$centerWidth = 70;
				$westWidth = $eastWidth = 30;
			}
		}
	?>
<div class="row">
	<?php
	if ($west) {
	?>
	<div class="col" style="width:<?php echo $westWidth; ?>%;">
		<div class="theme-west-container">
			<div class="theme-west">
				<?php $template->west(); ?>
			</div>
		</div>
	</div>
	<?php
	}
	?>
	
	<div class="col" style="width:<?php echo $centerWidth; ?>%;">
	
		<div class="theme-center-container">
			<div class="theme-center">
				<?php
				$template->center();
				?>
			</div>
		</div>
		
	</div>
	
	<?php
	if ($east) {
	?>
	<div class="col" style="width:<?php echo $eastWidth; ?>%;">
		<div class="theme-east-container">
			<div class="theme-east">
				<?php
				$template->east();
				?>
			</div>
		</div>
	</div>
	<?php
	}
	?>
	<div class="clear-left"></div>
</div>
	<?php
	}

    /**
     * 
     * 底部
     */
    protected function south()
    {
    }



}
