#!/usr/bin/env python
'''   
 * Catroid: An on-device visual programming system for Android devices
 * Copyright (C) 2010-2013 The Catrobat Team
 * (<http://developer.catrobat.org/credits>)
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 * 
 * An additional term exception under section 7 of the GNU Affero
 * General Public License, version 3, is available at
 * http://developer.catrobat.org/license_additional_term
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
'''

import fileinput, glob, os, shutil, sys, tools
from remoteShell import RemoteShell
from sql import Sql

#~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class PermissionChecker:
	basePath					= os.getcwd()
	folders						= [os.path.join('addons', 'board', 'cache'),
											os.path.join('addons', 'board', 'images', 'avatars', 'upload'),
											'cache', 
											os.path.join('resources', 'catroid'),
											os.path.join('resources', 'projects'),
											os.path.join('resources', 'qrcodes'),
											os.path.join('resources', 'thumbnails'),
											os.path.join('include', 'xml', 'lang'),
											os.path.join('tests', 'phpunit', 'framework', 'testdata')]

	#--------------------------------------------------------------------------------------------------------------------
	def run(self):
		for folder in self.folders:
			path = os.path.join(self.basePath, folder)
			if(os.stat(path).st_mode & 0777) != 0777:
				print 'setting permissions for ' + path
				os.system('sudo chmod -R 0777 ' + path)


#~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
class SetupBackup:
	basePath					= os.getcwd()
	remoteDir				  = os.path.join('home', 'catback', 'backup')

	def init(self):
		#shell = RemoteShell('jenkinsmaster', 'catback', '', remoteDir=self.remoteDir)
		shell = RemoteShell('192.168.1.113', 'chris', '', remoteDir=self.remoteDir)
		try:
			shell.sftp.mkdir('backup')
		except:
			pass
		shell.sftp.put(os.path.join(self.basePath, 'services', 'backup.py'), os.path.join('backup', 'backup.py'))
		shell.sftp.put(os.path.join(self.basePath, 'services', 'remoteShell.py'), os.path.join('backup', 'remoteShell.py'))
		shell.sftp.put(os.path.join(self.basePath, 'services', 'sql.py'), os.path.join('backup', 'sql.py'))
		shell.sftp.put(os.path.join(self.basePath, 'services', 'init', 'backup', 'backup_daemon.sh'), os.path.join('backup', 'backup_daemon.sh'))
		shell.sftp.put(os.path.join(self.basePath, 'services', 'init', 'backup', 'backup_setup.sh'), os.path.join('backup', 'backup_setup.sh'))

		try:
			shell.sftp.mkdir(os.path.join('backup', 'sql'))
			shell.sftp.mkdir(os.path.join('backup', 'sql', 'catroboard'))
			shell.sftp.mkdir(os.path.join('backup', 'sql', 'catroweb'))
			shell.sftp.mkdir(os.path.join('backup', 'sql', 'catrowiki'))
		except:
			pass
				
				
#~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
## command handler
if __name__ == '__main__':
	if len(sys.argv) > 1:
		if sys.argv[1] == 'website':
			PermissionChecker().run()
			Sql().initDbs()
			tools.Selenium().update()
			tools.JSCompiler().update()
			tools.CSSCompiler().update()
		elif sys.argv[1] == 'backup':
			SetupBackup().init()