#!/usr/bin/python

import sys, os, commands


potfiles = []
for root, dirs, files in os.walk('.'):
    if root.find(".git") > -1 or root == "." or root.find('forstats') > -1:
        continue

    root = root[2:]
    for i in files:
        if i.find('.pot') > 0:
            potfiles.append( root + "/" + i );
            print root + "/" + i

def getphase(s):
    return int(s[s.find('phase') + len('phase')])

def findmin(linesread):
    min = ""
    indexes = []
    for i in range(len(linesread)):
        if linesread[i] != "":
            min = linesread[i]
            indexes.append(i)
            break
    for i in range(indexes[0]+1, len(linesread)):
        if linesread[i] < min and linesread[i] != "":
            min = linesread[i]
            indexes = [i]
        elif linesread[i] == min:
            indexes.append(i)
    return min, indexes

def gettime(p):
    log = p.replace('john.pot', 'john.log')
    if log == p: return ""
    
    f = open(log, 'r')
    totalline = ''
    for line in f:
        totalline = line
    return totalline[2:12]


def uniqueness(potfiles, f):
    whandles = []
    linesread = []
    numlines = []
    uniquelines = []
    for w in potfiles:
        whandles.append(open(w, 'r'))
        linesread.append("")
        numlines.append(0)
        uniquelines.append(0)

    count = range(len(whandles))
    for i in count:
        linesread[i] = whandles[i].readline().strip()
        numlines[i] += 1

    while True:
        (min, indexes) = findmin(linesread)
        if len(indexes) == 1:
            uniquelines[indexes[0]] += 1
        for i in indexes:
            linesread[i] = whandles[i].readline().strip()
            numlines[i] += 1
            if linesread[i] == "":
                numlines[i] -= 1
                whandles[i] = 0
        if not any(linesread):
            break

    for i in count:
        f.write(str(uniquelines[i]) + "\tunique cracks, " + str(numlines[i]) + "\tcracks in " + gettime(potfiles[i]) + " in " + potfiles[i] + "\n")
        print str(uniquelines[i]) + "\tunique cracks, " + str(numlines[i]) + "\tcracks in " + gettime(potfiles[i]) + " in " + potfiles[i]

for i in [1,2,3,4,5]:
    print "Calculating uniqueness for phase ", i
    f = open('stats.p' + str(i), 'w');
    uniqueness([p for p in potfiles if getphase(p) <= i], f)
    f.close()

rulesp1 = {}
rulesp3 = {}
rulesp4 = {}
rulesp5 = {}
for p in potfiles:
    if getphase(p) in [1, 3, 4, 5]:
        log = p.replace('john.pot', 'john.log')
        if log == p: continue
        
        f = open(log, 'r')
        ruleline = 'catch-all'

        rules = rulesp1
        if getphase(p) == 3:
            rules = rulesp3
        elif getphase(p) == 4:
            rules = rulesp4
        elif getphase(p) == 5:
            rules = rulesp5
            
        rules['catch-all'] = 0

        for line in f:
            if line.find(' - Rule #') > 0:
                ruleline = line[line.find(' - Rule #'):].strip()
                if ruleline not in rules: rules[ruleline] = 0
            elif line.find(' + Cracked ') > 0:
                rules[ruleline] += 1

f = open('rules.p1', 'w')
for r in rulesp1:
    f.write(str(rulesp1[r]) + "\tcracked by " + r + "\n")
f.close();

f = open('rules.p3', 'w')
for r in rulesp3:
    f.write(str(rulesp3[r]) + "\tcracked by " + r + "\n")
f.close();

f = open('rules.p4', 'w')
for r in rulesp4:
    f.write(str(rulesp4[r]) + "\tcracked by " + r + "\n")
f.close();

f = open('rules.p5', 'w')
for r in rulesp5:
    f.write(str(rulesp5[r]) + "\tcracked by " + r + "\n")
f.close();

