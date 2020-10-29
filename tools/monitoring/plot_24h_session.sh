tail -288 /data/monitoring/session.log.csv > /data/monitoring/24h_session.csv
awk '{print $1$3,$5}' /data/monitoring/24h_session.csv > /data/monitoring/plot_24h_session.dat
gnuplot /data/monitoring/plot_24h_session.conf
cp /data/monitoring/graph_24h_user.png /data/metadata/
