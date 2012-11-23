#!/bin/env python
#  Redistribution and use in source and binary forms, with or without
#  modification, are permitted provided that the following conditions are
#  met:
#  
#  (C) 2012 James Finstrom <jfinstrom(at)qturbo.com>
#  (C) 2012	QTurbo LLC
#
#  * Redistributions of source code must retain the above copyright
#    notice, this list of conditions and the following disclaimer.
#  * Redistributions in binary form must reproduce the above
#    copyright notice, this list of conditions and the following disclaimer
#    in the documentation and/or other materials provided with the
#    distribution.
#  * Neither the name of the  nor the names of its
#    contributors may be used to endorse or promote products derived from
#    this software without specific prior written permission.
#  
#  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
#  "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
#  LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
#  A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
#  OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
#  SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
#  LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
#  DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
#  THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
#  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
#  OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
#  
VERSION = 1.0

import subprocess
import os
import sys
import time
import datetime
import logging
import pickle
#What span numbers to watch
spans = ['1','2']
#who is the ALERT email from?
email_from = 'user@site.tld'
#who should be emailed for "alerts"
emails = ['user@site.tld','user@site.tld']
#where should I log
logfile = '/var/log/span_log'
#setup logging
logger = logging.getLogger(sys.argv[0])
hdlr = logging.FileHandler(logfile)
formatter = logging.Formatter('%(asctime)s %(levelname)s %(message)s')
hdlr.setFormatter(formatter)
logger.addHandler(hdlr)
logger.setLevel(logging.INFO)
##everything else below here is generally hands off
out = []
odict = {}
def send_update(msg):
	for i in emails:
		logger.info("You told me to so I am emailing %s" % i)
	SENDMAIL = "/usr/sbin/sendmail" # sendmail location
	p = os.popen("%s -t" % SENDMAIL, "w")
	for i in emails:
		p.write("To:%s\n" %i)
	p.write("From:%s\n" % email_from)
	p.write("Subject: SPAN STATUS UPDATE\n")
	p.write("\n") # blank line separating headers from body
	p.write("%s\n" % msg)
	sts = p.close()
	logger.info("Email exit: %s " % str(sts))
	
def call_the_calvery(span,status):
	#no need to panic we have this
	if os.path.isfile("/tmp/last_span%s.p" % span):
		last_state = pickle.load( open( "/tmp/last_span%s.p" % span, "rb"))
	else:
		logger.info("It seems this is the first run so no last state for %s" % span )
		last_state = ''
	if last_state == status:
		logger.info("%s still in %s" % (span,status))
	else:
		logger.info("NEW ALARM STATE %s on SPAN %s" % (status, span))
		send_update("Sorry to bother you but it seems we have an issue.\n Span %s has an alarm status of %s\n" % (status,span))
		pickle.dump( status, open( "/tmp/last_span%s.p" % span, "wb" ) )
		
def all_clear(span):
	if os.path.isfile("/tmp/last_span%s.p" % span):
		last_state = pickle.load( open( "/tmp/last_span%s.p" % span, "rb"))
	else:
		logger.info("It seems this is the first run so no last state for %s" % span )
		last_state = ''
	if last_state == "OK":
		#nothing to do so lets move on
		return 0
	else:
		pickle.dump( "OK" , open( "/tmp/last_span%s.p" % span, "wb" ) )
		logger.info("span %s now OK" % span )
		send_update("I have fantastic news\n Span %s is now OK\n If this is your doing\n THANK YOU" % span)

def main():
	logger.info('Check Started')
	for i in spans:
		logger.info('Getting data from span %s' % i)
		p1 = subprocess.Popen(["dahdi_scan", i ], stdout=subprocess.PIPE)
		output = p1.communicate()[0]
		if len(output) < 3:
			logger.info("No span info for specified span %s does this span exist?" % i)
			continue
		odict = {}
		logger.debug('parsing output of dahdi_scan %s', i)
		for line in output.split('\n'):
			logger.debug('output: %s', line)
			if '=' not in line:
				continue
			p,o = line.split('=')
			logger.debug('span %s: %s = %s', i,p,o)
			odict[p] = o
			out.append(odict)
	for i in spans:
		if len(i) < 3:
			continue
		logger.debug('Span %s alarm status %s' % (i,out[ int(i)-1 ]['alarms']))
		if out[ int(i)-1 ]['alarms'] == 'OK':
			all_clear(i)
		else:
			logger.info('Span %s alarm status %s and we are not cool with that'% (i,out[ int(i)-1 ]['alarms']))
			call_the_calvery(i,out[ int(i)-1 ]['alarms'])
	return 0
			
if __name__ == '__main__':
	main()

