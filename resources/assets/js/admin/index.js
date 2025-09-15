`use strict`;

import Alpine from 'alpinejs';
import mask from '@alpinejs/mask'
import sort from '@alpinejs/sort'
import Tooltip from "@ryangjchandler/alpine-tooltip";

import { listView } from './list.js';
import { presetView } from './preset.js';
import { userView } from './user.js';
import { categoryView } from './category.js';
import { planView } from './plan.js';
import { settingsView } from './settings.js';
import { pluginsView } from './plugins.js';
import { pluginView } from './plugin.js';
import { workspaceView } from './workspace.js';
import { subscriptionView } from './subscription.js';
import { planSnapshowView } from './plan-snapshot.js';
import { dashboardView } from './dashboard.js';
import { analyticsView } from './analytics.js';
import { updateView } from './update.js';
import { assistantView } from './assistant.js';
import { voiceView } from './voice.js';
import { payoutView } from './payout.js';
import { couponView } from './coupon.js';
import { orderView } from './order.js';
import { licenseView } from './license.js';
// Load views
listView();

dashboardView();
analyticsView();
presetView();
userView();
workspaceView();
categoryView();
planView();
pluginsView();
pluginView();
subscriptionView();
planSnapshowView();
settingsView();
updateView();
assistantView();
voiceView();
payoutView();
couponView();
orderView();
licenseView();

// Call after views are loaded
Alpine.plugin(mask);
Alpine.plugin(sort);
Alpine.plugin(Tooltip.defaultProps({ arrow: false }));
Alpine.start();

window.Alpine = Alpine;