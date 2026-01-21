#!/usr/bin/env bash

rm -f /usr/local/bin/network.alias.local
rm -f /Library/LaunchDaemons/com.user.localhost-alias.plist

touch /usr/local/bin/network.alias.local
chmod +x /usr/local/bin/network.alias.local
touch /Library/LaunchDaemons/com.user.localhost-alias.plist

echo '#!/usr/bin/env bash

from=${1}
to=${2}

if [[ -z "$from" || -z "$to" ]]; then
  echo "Usage: choose range from 2 to 255. By defefault: aliases range sets from 127.0.0.2 to 127.0.0.4"
  exit 0
fi 

for ((i=${from};i<=${to};i++))
do
    sudo ifconfig lo0 alias 127.0.0.$i up
done' >> /usr/local/bin/network.alias.local 

echo '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple Computer//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>com.user.localhost-alias</string>
    <key>RunAtLoad</key>
    <true/>
    <key>ProgramArguments</key>
    <array>
      <string>/usr/local/bin/network.alias.local</string>
      <string>2</string>
      <string>4</string>
    </array>
</dict>
</plist>' >> /Library/LaunchDaemons/com.user.localhost-alias.plist

echo "Done!"

