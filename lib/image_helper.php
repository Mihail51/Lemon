<?php

function recipe_image($image, $type = 'main')
{
    if (!$image) return null;

    if ($type === 'preview') {
        return str_replace('.jpg','_preview.jpg',$image);
    }

    if ($type === 'gallery') {
        return str_replace('.jpg','_gallery.jpg',$image);
    }

    return $image;
}

function recipe_image_webp($image, $type = 'main')
{
    if (!$image) return null;

    if ($type === 'preview') {
        return str_replace('.jpg','_preview.webp',$image);
    }

    if ($type === 'gallery') {
        return str_replace('.jpg','_gallery.webp',$image);
    }

    return str_replace('.jpg','.webp',$image);
}