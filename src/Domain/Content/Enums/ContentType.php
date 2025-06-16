<?php

namespace Src\Domain\Content\Enums;

enum ContentType: string
{
    case ARTICLE = 'ARTICLE';
    case PAGE = 'PAGE';
    case MEDIA = 'MEDIA';
}