<?php

Page_Controller::add_extension("Vision6PageControllerExtension");
ShortcodeParser::get('default')->register("vision6_list", array("Vision6Extension", "ShortCodeVision6List"));
