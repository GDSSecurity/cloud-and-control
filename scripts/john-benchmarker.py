#!/usr/bin/python

import sys, os

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
	
def printcsv(machines, benchfilter, type):
	f = open('benchmarks.csv', 'w')
	delim = ','
	
	m_names = [m for m in machines]
	m_names.sort()
	p_names = list()
	for m in m_names:
		for p in machines[m]:
			p_names.append(p)
	p_names = list(set(p_names))
	p_names.sort()
	
	f.write(delim)#blank first column
	for p in p_names:#first row of benchmark names
		f.write(p + delim)
	f.write("\n")
	
	for m in m_names:
		f.write(m + delim)
		for p in p_names:
			outputForThisPatch = 0
			if p in machines[m]:
				for b in machines[m][p]:
					if b.find(benchfilter) >= 0 and b.find(type) >= 0:
						if outputForThisPatch == 1:
							print "Matched two benchmarks. No 3D Spreadsheets for you!"
							sys.exit(1);
						f.write(machines[m][p][b] + delim)
						outputForThisPatch = 1
			else:
				f.write(delim)
		f.write("\n")
				
	f.close()

def printtoscreen(machines, benchfilter, type):
	m_names = [m for m in machines]
	m_names.sort()
	p_names = list()
	for m in m_names:
		for p in machines[m]:
			p_names.append(p)
	p_names = list(set(p_names))
	p_names.sort()
	
	for m in m_names:
		print m
		for p in p_names:
			outputForThisPatch = 0
			if p in machines[m]:
				for b in machines[m][p]:
					if b.find(benchfilter) >= 0 and b.find(type) >= 0:
						if outputForThisPatch == 1:
							print "Matched two benchmarks. No 3D Spreadsheets for you!"
							sys.exit(1);
						print "\t", p, ":", b, ":", machines[m][p][b]
						outputForThisPatch = 1
	
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

	printtoscreen(machines, 'LM DES [128/128 BS SSE2', '')
	printcsv(machines, 'LM DES [128/128 BS SSE2', '')
	