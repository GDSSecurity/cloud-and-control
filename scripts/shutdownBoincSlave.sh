#!/bin/bash

echo "Entering infinite check-shutdown loop.";

while [ 1 = 1 ];
do

    lines=$(/usr/local/bin/boinccmd --host localhost --passwd $(cat /var/lib/boinc/gui_rpc_auth.cfg) --get_tasks | wc -l)

    if [ $lines -gt 2 ]; then
        echo "Found $lines lines - looks like I have a task"
        sleep 10
        continue
    fi

    run=$(curl http://ritter.vg/misc/boinc-cmd.txt 2>/dev/null | grep run | wc -l)
    shutdown=$(curl http://ritter.vg/misc/boinc-cmd.txt 2>/dev/null | grep shutdown | wc -l)

    if [ $run -eq 0 -a $shutdown -eq 1 ]; then
        echo "Shutting Down"
    else
        echo "Didn't get correct incantation for run command"
    fi

    sleep 10

done;
