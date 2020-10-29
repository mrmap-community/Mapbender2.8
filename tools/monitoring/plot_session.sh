awk '{print $1$3,$5}' /data/monitoring/session.log.csv > /data/monitoring/plot_session.dat
gnuplot /data/monitoring/plot_session.conf
cp /data/monitoring/graph_user.png /data/metadata/
