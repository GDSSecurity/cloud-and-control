#!/usr/bin/python

import sys, os, commands
from goto import goto, label

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

def genUniqueness(path):
    wordlists = []
    linecount = []

    os.system("rm -rf " + path + "-repeats/*")    

    for root, dirs, files in os.walk(path):
        if root.find(".git") > -1 or root == ".":
            continue
        if root.find("onlyuppercase") > -1:
            continue

        for i in files:
            if i.find('lvl') >= 0 or i.find('trimmed') >= 0:
                wordlists.append( root + "/" + i );
                linecount.append(int(commands.getoutput("cat " + root + "/" + i + " | wc -l")))
                print root + "/" + i

    repeatfiles = {}
    whandles = []
    linesread = []
    numlines = []
    uniquelines = []
    for w in wordlists:
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
        else:
            if len(indexes) not in repeatfiles: repeatfiles[len(indexes)] = open(path + '-repeats/repeat-' + str(len(indexes)), 'w')
            repeatfiles[len(indexes)].write(min + "\n")

        for i in indexes:
            linesread[i] = whandles[i].readline().strip()
            numlines[i] += 1
            if linesread[i] == "":
                numlines[i] -= 1
                whandles[i] = 0
        if not any(linesread):
            break

            
genUniqueness('lvl185')
