<?php

namespace Presentation\AccessControls;

enum Permission
{
    case WORKSPACE_MANAGE;
    case LIBRARY_ITEM_DELETE;
    case LIBRARY_ITEM_EDIT;
    case LIBRARY_ITEM_READ;
    case VOICE_DELETE;
    case VOICE_EDIT;
    case VOICE_USE;
}
