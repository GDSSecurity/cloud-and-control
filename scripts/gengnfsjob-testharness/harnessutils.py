import random, os, sys, subprocess

def runtest(digits, factmsieve):
	testfile_fb = str(digits) + 'digittest'
	factmsieve.NAME = testfile_fb
	factmsieve.LOGNAME = testfile_fb + '.log'
	factmsieve.JOBNAME = testfile_fb + '.job'
	factmsieve.ININAME = testfile_fb + '.ini'
	factmsieve.DATNAME = testfile_fb + '.dat'
	factmsieve.FBNAME = testfile_fb + '.fb'

	sys.stdout = open(factmsieve.LOGNAME, 'a')

	write_fb_file(digits, testfile_fb)
	factmsieve.fb_to_poly()
	factmsieve.read_parameters(factmsieve.fact_p, factmsieve.poly_p, factmsieve.lats_p)
	factmsieve.check_parameters(factmsieve.fact_p, factmsieve.poly_p, factmsieve.lats_p)
	factmsieve.setup(factmsieve.fact_p, factmsieve.poly_p, factmsieve.lats_p, 1, 1, 1)
	factmsieve.make_sieve_jobfile(factmsieve.JOBNAME, factmsieve.fact_p, factmsieve.poly_p, factmsieve.lats_p)

	subprocess.call([ './gengnfsjob.php', testfile_fb + '.fb'], stdout=subprocess.PIPE)
	
	sys.stdout.close()
	sys.stdout = sys.__stdout__

	f = open(factmsieve.JOBNAME + '.T0', 'r')
	theirjobfile = f.read()
	f.close()
	f = open(factmsieve.JOBNAME, 'r')
	myjobfile = f.read()
	f.close()

	return (theirjobfile.splitlines(), myjobfile.splitlines())

def jobsdiffer(theirs, mine):
	if len(theirs) != len(mine) + 1:
		return True
	for i in range(0, len(mine)):
		if mine[i] != theirs[i]:
			if mine[i].find('q0') == 0 and theirs[i].find('q0') == 0:
				pass
			elif mine[i].find('qintsize') == 0 and theirs[i].find('qintsize') == 0:
				pass
			else:
				return True
	return False
	
def write_fb_file(digits, outfile):
	number = "1" * digits
	
	with open(outfile + '.fb', 'w') as out:
		out.write("N " + str(number) + "\n")
		out.write("SKEW " + str(random.randint(1000000, 3000000)) + "." + str(random.randint(10, 100)) + "\n")
		out.write("A4 " + str(random.randint(1000, 5000)) + "\n")
		out.write("A3 " + str(random.randint(1000000000, 5000000000)) + "\n")
		out.write("A2 " + str(random.randint(-50000000000000000, -10000000000000000)) + "\n")
		out.write("A1 " + str(random.randint(-9000000000000000000000, -7000000000000000000000)) + "\n")
		out.write("A0 " + str(random.randint(1000000000000000000000000000, 3000000000000000000000000000)) + "\n")
		out.write("R1 " + str(random.randint(60000000000000, 90000000000000)) + "\n")
		out.write("R0 " + str(random.randint(-900000000000000000000000, -600000000000000000000000)) + "\n")
		out.write("FAMAX " + str(random.randint(1800000, 1800000)) + "\n")
		out.write("FRMAX " + str(random.randint(1800000, 1800000)) + "\n")
		out.write("SALPMAX " + str(random.randint(67000000, 69000000)) + "\n")
		out.write("SRLPMAX " + str(random.randint(67000000, 69000000)) + "\n")

def cleanup(digits, keepJobFile):
	testfile_fb = str(digits) + 'digittest'
	os.remove(testfile_fb + '.log')
	os.remove(testfile_fb + '.ini')
	os.remove(testfile_fb + '.poly')
	os.remove(testfile_fb + '.dat')
	os.remove(testfile_fb + '.fb')
	if not keepJobFile:
		os.remove(testfile_fb + '.job.T0')
		os.remove(testfile_fb + '.job')
	else:
		os.rename(testfile_fb + '.job.T0', testfile_fb + '.theirjob')
		os.rename(testfile_fb + '.job', testfile_fb + '.myjob')

