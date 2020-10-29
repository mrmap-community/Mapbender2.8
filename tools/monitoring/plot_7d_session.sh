tail -2016 /data/monitoring/session.log.csv > /data/monitoring/7d_session.csv
awk '{print $1$3,$5}' /data/monitoring/7d_session.csv > /data/monitoring/plot_7d_session.dat
gnuplot /data/monitoring/plot_7d_session.conf
cp /data/monitoring/graph_7d_user.png /data/metadata/
