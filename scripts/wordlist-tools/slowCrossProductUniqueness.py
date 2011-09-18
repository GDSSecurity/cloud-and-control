#!/usr/bin/python

import sys, os, commands

def calculateoverlap(a,b):
    c ="comm " + a + " " + b + " -1 -3 | wc -l"
    onlyinb = commands.getoutput(c)
    c = "cat " + b + " | wc -l "
    lenofb = commands.getoutput(c)
    return round(float(onlyinb) / float(lenofb), 4) * 100;

def calculateagainstall(a,wordlists):
    c="cat bnew.txt rocku.txt cat.txt "
    for i in wordlists:
        if a != i:
            c += i + " "
    c += " | sort | uniq > tmpdsf"
    commands.getoutput(c)
    c ="comm tmpdsf " + a + " -1 -3 | wc -l"
    onlyina = commands.getoutput(c)
    c = "cat " + a + " | wc -l "
    lenofa = commands.getoutput(c)
    commands.getoutput("rm tmpdsf")
    return round(float(onlyina) / float(lenofa), 4) * 100;

def genMatrix(path):
	# =============================================================
    wordlists = ["bnew.txt", "rocku.txt", "cat.txt"]
    for root, dirs, files in os.walk('phase3'):
        if root.find(".git") > -1 or root == ".":
            continue
        if root.find("onlyuppercase") > -1:
            continue

        for i in files:
            if i.find('lvl') == 0:
                wordlists.append( root + "/" + i );
                print root + "/" + i

	# =============================================================
    overlap = {};
    for a in wordlists:
        for b in wordlists:
            if a not in overlap:
                overlap[a] = {}
            overlap[a][b] = calculateoverlap(a, b);
            print 'Comparing ',a,b,': b is ', str(overlap[a][b]), ' percent unique'
        overlap[a]['everythingelse'] = calculateagainstall(a, wordlists)

	# =============================================================
    f = open('overlapmatrix.'+path+'.csv', 'w')
    delim = ','
    f.write(delim)#blank first column
    for p in wordlists:#first row of benchmark names
        f.write(p + delim)
    f.write("everythingelse" + delim)
    f.write("\n")

    for a in wordlists:
        f.write(a + delim)
        for b in wordlists:
            f.write(str(overlap[a][b]) + delim)
        f.write(str(overlap[a]['everythingelse']) + delim)
        f.write("\n")
    f.close()

genMatrix('phase3')
