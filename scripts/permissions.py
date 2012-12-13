import os
import stat

directories = dict([
  ('addons/board/cache', oct(0777)), 
  ('addons/board/images/avatars/upload', oct(0777)), 
  ('resources/catroid', oct(0777)), 
  ('resources/projects', oct(0777)),
  ('resources/qrcodes', oct(0777)), 
  ('resources/thumbnails', oct(0777)), 
  ('include/xml/lang', oct(0777)), 
  ('tests/phpunit/framework/testdata', oct(0777))
])

print '\n# setting permissions...\n'
try:
  success = ""
  print 'Please enter your password, it is necessary to change permissions: '
  for path, perm in directories.iteritems():
    if os.path.isdir(path):
      new_perm = oct(0)
      current_perm = oct(stat.S_IMODE(os.stat(path)[stat.ST_MODE]))
      if(current_perm == perm):
            success = "\tUNCHANGED."
            new_perm = current_perm
      else:
        if(current_perm != perm):
          os.system('sudo chmod -R ' + str(perm) + ' ' +  str(path))
          success = "\tOK."
          new_perm = oct(stat.S_IMODE(os.stat(path)[stat.ST_MODE]))
        elif(current_perm == perm):
          new_perm = current_perm
        
        if(new_perm != perm):
          success = "\tNOT OK."
        
      print " > " + path + ': ' + str(current_perm) + ' -> ' + str(new_perm) + success
    else:
      print ' > ERROR: No such directory \'' + path + '\''
finally:
    print '\n# done.'
    
