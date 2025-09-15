`use strict`;

import Alpine from 'alpinejs';
import Tooltip from "@ryangjchandler/alpine-tooltip";

import { initState } from './state.js';

import { listView } from './list.js';
import { billingView } from './billing.js';
import { checkoutView } from './checkout.js';
import { accountView } from './account.js';
import { dashboardView } from './dashboard.js';
import { voiceover } from './voiceover.js';
import { imagineView } from './imagine.js';
import { workspace } from './workspace.js';
import { writerView } from './writer.js';
import { coderView } from './coder.js';
import { transcriberView } from './transcriber.js';
import { chat } from './chat.js';
import { voiceIsolator } from './voice-isolator.js';
import { classifier } from './classifier.js';
import { affiliatesView } from './affiliates.js';
import { composerView } from './composer.js';
import { videoView } from './video.js';
import { modelsView } from './models.js';
import { mask } from '@alpinejs/mask';

initState();
dashboardView();
listView();
writerView();
coderView();
imagineView();
transcriberView();
billingView();
accountView();
voiceover();
workspace();
checkoutView();
chat();
voiceIsolator();
classifier();
affiliatesView();
composerView();
videoView();
modelsView();

Alpine.plugin(mask);
Alpine.plugin(Tooltip.defaultProps({ arrow: false }));
Alpine.start();