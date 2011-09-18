#!/usr/bin/python

#make sure msieve is there
#and the seivers

import factmsieve, sys
from harnessutils import *

factmsieve.CHECK_POLY = False
factmsieve.DEFAULT_PAR_FILE = 'def-par.txt'
factmsieve.MSIEVE_PATH = './'
factmsieve.GGNFS_PATH = './'
factmsieve.MSIEVE = 'binarystub'
factmsieve.client_id = 1
factmsieve.NUM_CORES = 1
factmsieve.THREADS_PER_CORE = 1
factmsieve.SV_THREADS = 1
factmsieve.LA_THREADS = 1
old_fact_p = factmsieve.fact_p
old_poly_p = factmsieve.poly_p
old_lats_p = factmsieve.lats_p

print "Running Tests..."
for digits in range(80, 170):
	theirs, mine = runtest(digits, factmsieve)
	different = jobsdiffer(theirs, mine)
	cleanup(digits, different)
	if different:
		print "Job Files differ for " + str(digits) + " digits."
	if digits % 20 == 0:
		print "Up to " + str(digits) + "..."
