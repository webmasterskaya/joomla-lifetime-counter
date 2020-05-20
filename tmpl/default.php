<?php
/**
 * @package    Joomla.Module - Lifetime counter
 * @version    __DEPLOY_VERSION__
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2020 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

defined('_JEXEC') or die;

$style = '';
$id    = '';
if (!empty($backgroundimage))
{
	$style = ' style="background-image: url(' . $backgroundimage . ');';
}
if (!empty($moduleid))
{
	$id = ' id="' . $moduleid . '"';
}

?>

<div class="lifetime-counter-container <?php echo $moduleclass; ?>"<?php echo $style; ?><?php echo $id; ?>>
	<?php if (!empty($preText)): ?>
        <span class="lifetime-counter-pretext"><?php echo $preText; ?></span>
	<?php endif; ?>
    <span class="lifetime-counter-counter"><?php echo $counter; ?></span>
	<?php if (!empty($postText)): ?>
        <span class="lifetime-counter-posttext"><?php echo $postText; ?></span>
	<?php endif; ?>
</div>