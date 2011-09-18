#!/usr/bin/python

import sys, os, commands
from goto import goto, label

class wordlist:
    lastline = ""
    handle = ""
    filename = ""
    ateof = False
    linesTested = 0
    def __init__(self, f):
        self.filename = f
        self.reset()
    def reset(self):
        self.ateof = False
        self.handle = open(self.filename, 'r')
        self.lastline = self.handle.readline()
        self.linesTested = 1
    def compareTo(self, line):
        label .startcompare
        if self.ateof:
            return False
        elif self.lastline > line:
            return False
        elif self.lastline == line:
            return True
        else:
            self.lastline = self.handle.readline()
            self.linesTested += 1
            if self.lastline == "":
                self.ateof = True
                return False
            goto .startcompare
    def getPos(self):
        return self.linesTested
    def getAllLines(self):
        self.reset()
        yield self.lastline
        for line in self.handle:
            yield line

def genMatrix(path):
    wordlists = []
    linecount = []
    
    log = open(path + ".uniqueness", 'w')

    for root, dirs, files in os.walk(path):
        if root.find(".git") > -1 or root == ".":
            continue
        if root.find("onlyuppercase") > -1:
            continue

        for i in files:
            if i.find('lvl') >= 0:
                wordlists.append( root + "/" + i );
                linecount.append(int(commands.getoutput("cat " + root + "/" + i + " | wc -l")))


    whandles = []
    for w in wordlists:
        whandles.append(wordlist(w))

    count = range(len(whandles))
    for i in count:
        uniques = 0
        numlines = 0

        sys.stdout.write("\rFile " + wordlists[i] + " 0%                   ")
        sys.stdout.flush()

        for srcline in whandles[i].getAllLines():
            isthislineunique = True

            for w in count:
                if i == w: continue
                
                if whandles[w].compareTo(srcline):
                    isthislineunique = False
                    
            numlines+=1
            if isthislineunique:
                uniques+=1

            if numlines % 250 == 0:
                percent = round(float(numlines) / linecount[i], 4)
                sys.stdout.write("\rFile " + wordlists[i] + " " + str(percent * 100) + "%               ")
                sys.stdout.flush()

        sys.stdout.write("\rFile " + wordlists[i] + " 100%         \n")
        print wordlists[i], uniques, numlines
        log.write(wordlists[i] + "," + str(uniques) + "," + str(numlines) + "\n")
        for w in count:
            whandles[w].reset()

    
genMatrix('phase4')
