#!/usr/bin/python

import sys, os, math

"""

	For a given version of john and a given 'standard' hash function
	This will tell you show much longer (or shorter) the other hash functions
	are to that standard one.  For example, MD5 is 5x faster than crypt, or
	DES is .05 times faster than crypt (meaning 20x slower)
	
	It does this across all the benchmarks, so you can see if a 'difficulty' rating
	of a hash function is consistent, or if it varies greatly...

"""


def uniquify(seq, idfun=None):  
    if idfun is None:
        def idfun(x): return x
    seen = {}
    result = []
    for item in seq:
        marker = idfun(item)
        if marker in seen: continue
        seen[marker] = 1
        result.append(item)
    return result
		
def getbenchmarks(dir, file, mode='real'):
	f = open(dir + "/" + file, 'r')

	benchmarks = {}
	current = ''
	for l in f:
		l = l.strip()
		
		#Empty Line
		if len(l) == 0:
			pass
		#New Benchmark?
		elif l.find('Benchmarking: ') == 0:
			bname = l[len('Benchmarking: ') : l.find('... DONE')].strip()
		#Line Item
		else:
			name = l[0 : l.find(':')]
			data = l[l.find(':')+1 : ].strip()
			data = data.partition(',')[2 if mode == 'virtual' else 0].replace('c/s ' + mode, '').strip()
			if data.endswith('K'):
				data = data.replace('K', '') + "000"
			benchmarks[bname + " - " + name] = data
	
	f.close()
		
	return benchmarks

def getNameArrays(machines, johnfilter, machinefilter, onlytheseformats, standardforcompare, standardsubtype):
	m_names = [m for m in machines if m.find(machinefilter) >= 0]
	m_names.sort()
	
	p_names = list()
	for m in m_names:
		for p in machines[m]:
			for f in johnfilter:
				if p.find(f) == 0:
					p_names.append(p)
	p_names = list(set(p_names))
	
	b_names = list()
	bstandard_names = list()
	for m in m_names:
		for p in p_names:
			for b in machines[m][p]:
				if len(onlytheseformats) == 0:
					if b.find(standardforcompare) >= 0 and b.find(standardsubtype) >= 0:
						bstandard_names.append(b)
					b_names.append(b)
				else:
					for a in onlytheseformats:
						if b.find(a) >= 0:
							if b.find(standardforcompare) >= 0 and b.find(standardsubtype) >= 0:
								bstandard_names.append(b)
							b_names.append(b)
	bstandard_names = list(set(bstandard_names))
	b_names = list(set(b_names))
	bstandard_names.sort()
	b_names.sort()
	
	return m_names, p_names, bstandard_names, b_names

def getStandardsForComparison(m_names, p_names, bstandard_names, machines):
	thestandardforcomparison = {}
	for m in m_names:
		thestandardforcomparison[m] = {}
		for p in p_names:
			thestandardforcomparison[m][p] = 0
			for b in bstandard_names:
				if b not in machines[m][p]:
					continue
				if thestandardforcomparison[m][p] != 0:
					print "Found a duplicate standard for comparison"
					sys.exit(1)
				thestandardforcomparison[m][p] = float(machines[m][p][b])
			if thestandardforcomparison[m][p] == 0:
				print "Found no standard for comparison"
				sys.exit(1)
	return thestandardforcomparison

def col2excelcol(col):
	if col <= 25:
		return chr(65+col)
	elif col / 26 < 26:
		ret = chr(64 + int(math.floor(float(col) / 26.0)))
		ret += chr(65 + (col % 26))
		return ret
	else:
		print "Crazy Fool"
		sys.exit(1)
def printcsv(machines, johnfilter, machinefilter, onlytheseformats, standardforcompare, standardsubtype):
	m_names, p_names, bstandard_names, b_names = \
		getNameArrays(machines, johnfilter, machinefilter, onlytheseformats, standardforcompare, standardsubtype)
	thestandardforcomparison = getStandardsForComparison(m_names, p_names, bstandard_names, machines)
	
	f = open('fractional-compare.csv', 'w')
	delim = ','
	
	f.write(delim)#first col
	for m in m_names:#first row of machine names
		for p in p_names:
			f.write(m.replace(delim, '') + " - " + p.replace(delim, '') + delim)
	f.write("Mean" + delim + "Std Dev." + delim + "CV\n")
	
	row = 2
	col = 1
	for b in b_names:
		f.write(b.replace(delim, '') + delim)
		for m in m_names:
			for p in p_names:
				if b in machines[m][p]:
					factor = float(machines[m][p][b]) / thestandardforcomparison[m][p]
					f.write(str(factor) + delim)
				else:
					f.write(delim)
				col += 1
		f.write("=AVERAGE(B" + str(row) +":" + col2excelcol(col-1) + str(row) + ")" + delim)
		f.write("=STDEV(B" + str(row) +":" + col2excelcol(col-1) + str(row) + ")" + delim)
		f.write("=" + col2excelcol(col+1) + str(row) + "/ABS(" + col2excelcol(col) + str(row) + ") * 100" + delim)
		f.write("=COUNTA(B" + str(row) +":" + col2excelcol(col-1) + str(row) + ")" + delim)
		f.write("\n")
		row += 1
		col = 1
	
	f.close()

def printtoscreen(machines, johnfilter, machinefilter, onlytheseformats, standardforcompare, standardsubtype):
	m_names, p_names, bstandard_names, b_names = \
		getNameArrays(machines, johnfilter, machinefilter, onlytheseformats, standardforcompare, standardsubtype)
	thestandardforcomparison = getStandardsForComparison(m_names, p_names, bstandard_names, machines)
	
	for m in m_names:
		for b in b_names:
			for p in p_names:
				if b not in machines[m][p]:
					continue
				factor = float(machines[m][p][b]) / thestandardforcomparison[m][p]
				#print "\t", p, b, ":", str(factor)
	
if __name__ == "__main__":
	machines = {};
	for root, dirs, files in os.walk('.'):
		if root.find(".svn") > -1 or root == ".":
			continue

		#if root.find('cluster-compute') >= 0 or root.find('c1.xlarge') >= 0: continue
			
		root = root[2:]
		for i in files:
			if i.find('.testresults') < 0:
				continue
				
			if root not in machines: machines[root] = {}
			machines[root][i.replace('.testresults', '').replace('-mpiexec', '-zmpiexec')] = getbenchmarks(root, i, 'real')

	johnversions = ['john-1.7.6', 'jumbo9', 'jumbo12']
	machinefilter = 'run'
	onlytheseformats = []#['DES', 'crypt', 'LM', 'NT', 'MD5']
	standardofcompare = 'Traditional DES [128/128 BS SSE2'
	standardsubtype = 'Many'
			
	printtoscreen(machines, johnversions, machinefilter, onlytheseformats, standardofcompare, standardsubtype)
	printcsv(machines, johnversions, machinefilter, onlytheseformats, standardofcompare, standardsubtype)
	