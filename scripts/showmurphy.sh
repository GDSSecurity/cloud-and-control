#!/bin/sh

find ./sample_results -maxdepth 1 -type f -and -size 1k | xargs -- grep -h Murphy | sed -e 's/\#Murphy //' | sort
