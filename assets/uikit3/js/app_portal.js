// CSS
require('../css/commsy.less');

require('nprogress/nprogress.css');
require('select2/dist/css/select2.css');
require("flatpickr/dist/themes/light.css");

// JS
const $ = require('jquery');
global.$ = global.jQuery = $;

require('expose-loader?exposes=NProgress!nprogress/nprogress');
require('moment/moment');
require('expose-loader?exposes=URI!urijs/src/URI');
require('select2/dist/js/select2');

import UIkit from 'uikit3';
import Icons from 'uikit3/dist/js/uikit-icons';

// loads the Icon plugin
UIkit.use(Icons);

// import {Edit} from "./commsy/Edit";
// Edit.bootstrap();

import {Upload} from "./commsy/Upload";
Upload.bootstrap();

import {DatePicker} from "./commsy/DatePicker";
DatePicker.bootstrap();

import {LicenseEdit} from "./commsy/LicenseEdit";
LicenseEdit.bootstrap();

import {FormCollection} from "./commsy/FormCollection";
FormCollection.bootstrap();

import {handleShibIdPSelect} from "./commsy/Login";
handleShibIdPSelect();