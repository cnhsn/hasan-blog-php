<?php
/** @var string $name */
/** @var string $value */
/** @var string $label */
?>
<div class="form-row">
  <label><?= e($label) ?></label>
  <div class="rich-text-editor" data-target="<?= e($name) ?>_hidden">
    <div class="editor-toolbar">
      <button type="button" data-cmd="bold"><b>B</b></button>
      <button type="button" data-cmd="italic"><i>I</i></button>
      <button type="button" data-cmd="underline"><u>U</u></button>
      <button type="button" data-cmd="strikeThrough"><s>S</s></button>
      <button type="button" data-cmd="formatBlock" data-value="h2">H2</button>
      <button type="button" data-cmd="formatBlock" data-value="h3">H3</button>
      <button type="button" data-cmd="formatBlock" data-value="blockquote">&ldquo;&rdquo;</button>
      <button type="button" data-cmd="insertUnorderedList">&bull; List</button>
      <button type="button" data-cmd="insertOrderedList">1. List</button>
      <button type="button" data-cmd="createLink">Link</button>
      <button type="button" data-cmd="insertCode">Code</button>
      <button type="button" data-cmd="insertCodeBlock">Code block</button>
      <button type="button" data-cmd="insertImage">Image</button>
    </div>
    <div class="editor-surface" contenteditable="true"></div>
  </div>
  <input type="hidden" name="<?= e($name) ?>" id="<?= e($name) ?>_hidden" value="<?= e($value) ?>">
</div>
