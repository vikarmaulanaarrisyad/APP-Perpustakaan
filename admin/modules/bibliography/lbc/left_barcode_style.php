<!-- Css left barcode -->
<style>
.row {
    display: block; width: 100%; margin-left: 20px; margin-top: 10px;
}

.sub-row {
    display: block; width: 100%;
}

.col {
    width: <?=$style['col']['width']?>px; display: inline-block; border: solid 2px black; height: auto;margin-left: 1px;
}

.left-barcode {
    transform: rotate(90deg);width: <?=$style['barcode-lr']['width']?>px;height: <?=$style['barcode-lr']['height']?>px;margin: <?=$style['barcode-lr']['left']['margin']?>;
}

.left-title {
    display: block;font-size: 8pt
}

.content {
    float: left;border-left: solid 2px black;height: <?=$style['content']['height']?>px;
}

.content-header {
    display: block; width: <?=$style['content-hm']['width']?>px; border-bottom: solid 2px black; padding: 1px;font-size: 8pt; text-transform: uppercase; font-weight: bold; text-align: center; padding-top: 2px; padding-bottom: 2px;
}

.content-main {
    display: block; width: <?=$style['content-hm']['width']?>px; padding: 1px;font-size: <?=$style['content']['font_size']?>pt;font-weight: bold; text-align: center;
}
</style>