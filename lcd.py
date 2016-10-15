#!/usr/bin/env python
# -*- coding: utf-8 -*-
from __future__ import print_function, division, absolute_import, unicode_literals

import sys

from RPLCD import CharLCD
from RPLCD import Alignment, CursorMode, ShiftMode
from RPLCD import cursor, cleared
from RPLCD import BacklightMode

try:
    input = raw_input
except NameError:
    pass

try:
    unichr = unichr
except NameError:
    unichr = chr


lcd = CharLCD()
# see note in test_16x2.py about configuring your backlight, if you have one

lcd.backlight = True

lcd.clear()

lcd.cursor_pos = (0, 0)
lcd.write_string('Temperatur:    12.3C')

lcd.cursor_pos = (1, 0)
lcd.write_string('Luftf.:          80%')

lcd.cursor_pos = (2, 0)
lcd.write_string('Luftdruck:   1.01kPa')

input('Waiting for ending')

lcd.clear()
lcd.backlight = False
lcd.close()
