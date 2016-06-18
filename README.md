# Biometric Multi-Factor Authentication Web Service and Application

> #### WARNING: THIS PROJECT IS PURELY FOR EDUCATIONAL PURPOSES. NONE OF THE CRYPTOGRAPHY HAS BEEN VERIFIED OR TESTED BY AN EXPERT THIRD PARTY. I ASSUME NO RESPONSIBILITY IF CODE FROM THIS PROJECT IS USED IN PRODUCTION SYSTEMS.

## Overview

This repository contains a web service and application I developed for my final year university project.

It is part of a biometric multi-factor authentication system. 
The RESTful JSON web service allows a symmetric challenge-response mutual authentication protocol to be performed, to verify the biometric identity of a user using cryptography (**HAVE** factor and **ARE** factor).
The web application provides a login system using traditional username and password credentials (**KNOW** factor). 
However, you are only able to login if your credentials are correct AND you have engaged in the biometric authentication protocol within the last 30 seconds.

## What problem is it tackling?

Single factor authentication is no longer sufficient for securing access to systems storing valuable personal, financial or corporate information (FFIEC, 2005). The number of increasing large data breaches in past years (Verizon, 2015) combined with password reuse across sites (Florencio & Herley, 2007) compounds the problems of relying solely upon a username and password.

By relying upon a biometric authentication procedure combined with the standard username and password, very high levels of security can be provided for electronic authentication. Compromising a victims username and password will no longer result in access to their account.

A massive constraint on this project was using a device with extremely limited resources. This meant that using HTTPS for communications, or any form of asymmetric cryptography for that matter was unavailable. Therefore, this project also offers solutions for authentication and encryption within the Internet of Things. This is discussed in more detail under 'Future Work'.

## Process

![Multi-factor Biometric Authentication](http://i.imgur.com/0c6DwbZ.png)

> This project assumed that the enrollment process would have already occured. This would involve the provider loading the biometric device with the pre-shared keys and storing these keys themselves within a database. The user would enroll their fingerprint on the biometric device locally, meaning they are the only ones who can initiate the authentication process.

**1.** First a user scans their fingerprint locally on a biometric device, which unlocks a set of symmetric cryptographic keys (see [this repository for the relevant code](https://github.com/JoeAlamo/Arduino)). The biometric device is incredibly resource constrained (**256KB program space, 8KB RAM**) which shaped the process and protocol.

**2.** These keys are used in a double challenge-response protocol with the RESTful JSON web service. This repository holds the code for that web service. 

**3.** If successful, the web service stores a time window in the database. This time window represents the amount of time the user has to log in to the website using their username and password.

**4.** The user navigates to the website using the device of their choice. They enter their username and password on the login page and submit the form.

**5.** The web application communicates with the database, verifying that the user exists and that the password is correct. Then the application also verifies that the login attempt is within the time window created during step 3. If so, access is granted. This repository also holds the code for the web application.

## Secure Authentication Protocol

The purpose of the secure authentication protocol is to verify to the server that the user is who they claim to be, and to also verify to the user that the server is authentic. It achieves this by relying upon symmetric keys which are only released upon successful local biometric verification. This means that no personal or private biometric data is ever transferred in the open, nor are large databases of biometric templates ever stored. One biometric template is stored on the users personal biometric device, which could be made tamper proof.

This approach follows [NIST's Electronic Authentication Guideline](http://nvlpubs.nist.gov/nistpubs/SpecialPublications/NIST.SP.800-63-2.pdf) (NIST, 2013) relating to authentication tokens.

![NIST's E-Auth Token Model](http://i.imgur.com/7GxHWZC.png)

The biometric device used in step 1 is a Multi-Factor Hardware Cryptographic Token, activated by a biometric (fingerprint - ARE factor) which releases symmetric keys (HAVE factor).

### Versions

During development, 3 versions of the protocol were designed and implemented. Each version of the protocol built upon the prior version, adding more security features. This was done because the project had a hard deadline. Protocols either work completely, or don't work at all. By incrementally developing the protocol in milestones, it meant that if development ran out of time there would still be a functioning product to demonstrate.

Version 1 was a very simple protocol to demonstrate a proof-of-concept of authentication between the biometric device and remote web application. Version 2 added mutual authentication using HMAC-SHA256 challenge response. Version 3 added encryption using unique session keys generated via HKDF (HMAC Key Derivation Function) to utilise ChaCha20-Poly1305 AEAD (Authenticated Encryption with Associated Data). 

### Secure Authentication Protocol (SAP) version 3

![SAPv3](http://i.imgur.com/DCQAKxp.png)

#### Fields (all are base64url encoded apart from expires):

**client_id**: 16 byte unique client identifier

**server_id**: 16 byte unique server identifier

**session_id**: 16 byte cryptographically secure unique random value to identify particular protocol session

**client_random**: 16 byte cryptographically secure unique random value to form part of client's challenge

**timestamp**: 4 byte Unix timestamp in little-endian format

**client_mac**: 16 byte MAC of client_id||server_id||session_id||client_random using the Authentication Key with HMAC-SHA256. Acts as the clients challenge

**server_mac**: 16 byte MAC of server_id||client_random using the Authentication key with HMAC-SHA256.

**ciphertext**: Contains an encrypted JSON object using the session key and a nonce with ChaCha20-Poly1305

**tag**: 16 byte MAC of session_id||ciphertext produced by the ChaCha20-Poly1305 algorithm

**expires**: An integer value specifying the amount of seconds that the biometric authentication will expire in

#### Stages

##### Stage 1

###### Request

The client issues a POST request to the /authentication/v3/biometric resource to create a new protocol session.

###### Response

The server creates a new session and responds with 201 Created, including the unique session_id and it's server_id.

##### Stage 2

###### Request

The client verifies the server_id comparing it to a pre-stored value. The client then retrieves a timestamp, either from an internal clock or NTP server. A 32 byte session key is then generated via HKDF (HMAC-SHA256). The extract stage uses timestamp||session_id as the salt and the Key Derivation Key as the input keying material to generate the pseudorandom key. The expand stage uses the previously generated pseudorandom key and client_id||server_id as the additional context information.

The client then randomly generates a client_random and calculates the client_mac. A JSON object containing client_random and client_mac acts as the ciphertext. This ciphertext is encrypted with the session key and a nonce value of 0 using ChaCha20-Poly1305, which produces a tag authenticating the ciphertext and session_id.

The client then issues a POST request to the /authentication/v3/biometric/<session_id> resource with the client_id, timestamp, ciphertext and tag as the payload.

###### Response

The server looks up and verifies that the client_id is linked to a valid client. The current timestamp is retrieved, and is compared to the most recent timestamp from that client. It must be greater than the previously received timestamp and within 10 minutes of the current time to ensure freshness whilst accommodating for clock skew.

The 32 byte session key is then generated in the same fashion as the client. This is used to verify that the tag is valid, using ChaCha20-Poly1305 with a nonce of 0. If so, the ciphertext is decrypted allowing the server to independently compute the client_mac and verify it's correctness. Depending on the outcome of this process, one of the following responses is issued:

1. **Success** - The server calculates the server_mac and activates the time-limited authentication period. The server_mac and expires fields are encrypted with the session key and a nonce value of 1, and the tag is produced as before. The server responds with 200 OK and a payload containing ciphertext and the tag. The client then verifies the tag, decrypts the ciphertext and independently verifies the server_mac. The client may then log in to the web application using their credentials.
2. **Unrecognised client_id, invalid timestamp, incorrect tag or invalid client_mac** - A 403 Forbidden response is issued.
3. **Invalid session_id** - A 404 Not Found response is issued.
4. **Invalid syntax** - A 400 Bad Request response is issued.

## Web authentication

The web authentication comprises mostly of the standard username/password authentication system omnipresent across the web. A very simplistic login page and home page were built to demonstrate the prototype system.
Passwords were hashed and verified using PHP's password hashing API. If the password was correct, the web application would verify that the user was logging in within a biometric authentication time window.

## Technologies

The web service and application was built using Silex as a bare bones framework to provide routing, request & response abstractions and an IoC container. This was chosen because the product is incredibly bespoke. A very lightweight framework offered freedom to architect an effective solution without being limited or boxed in.

For the cryptography a mixture of technologies were used. PHP's built in hash_hmac API was used for the calculation of the challenge/response MACs and also within the session key derivation (HKDF). The libsodium-php extension was used to get access to libsodiums implementation of ChaCha20-Poly1305 for authenticated encryption.

## Architecture

### Schema

![Database schema](http://i.imgur.com/HvfzR3G.png)

The schema is separated into several areas. The biometric_client table is the main table, and also acts as a gateway connecting the whole process to the web application, containing a table of users. In this manner, the authentication system is pluggable into existing systems with a small bit of tinkering. This table holds the client's unique ID as well as their pre-shared keys used within the authentication protocol. 

The other main tables are biometric_session and biometric_authenticated_session. Entries are created within the biometric_session table whenever a client initiates the authentication protocol. Upon a successful protocol run, an entry is added to biometric_authenticated_session which links to the protocol run and includes the time window within which the client may log in to the website using their username and password.

The nonce caches (previous_client_random and previous_client_timestamp) are used to hold previous values which should be unique. These are key to ensuring the freshness of authentication protocol messages and preventing replay attacks.

The failed verification and rate limiting tables are an extension of the work. They are used to prevent brute forcing attacks on the protocol. If a protocol run fails, entries are added to failed_session_attempt. If enough of these occur within a timeframe, an entry is added to biometric_session_block and any protocol runs made by the client are prevented for a period of time. Similarly, if a client scans an incorrect finger repeatedly, the biometric device contacts the server and informs the server of the failed biometric verification attempts. This will also trigger rate limiting.

### Web application and service structure

![Layered architecture](http://i.imgur.com/0SK9VPh.png)

A layered architecture was designed and implemented, with each layer serving a specific purpose. Requests are made to resources (endpoints). These endpoints then correspond to methods within Controllers, whose responsibility is solely dealing with requests, and issuing responses. The controllers call the relevant Services which is where the important protocol logic occurs. As HTTP is a stateless protocol, the state of the protocol run has to be retrieved. The services do this by interacting with Repositories, which abstract away the communications with the database. These repositories then return either raw data or Models which encapsulate certain entities within the system. The services carry out their processing and then instruct the controller to send a certain response. The controller constructs the response in the correct format and sends it back to the client.

![Interfaces separating layers](http://i.imgur.com/wkd4gPE.png)

To implement this layered architecture interfaces were heavily used between each layer. This was extremely important because three versions of the protocol were being developed. This allowed changes to be made within the layers without affecting other parts of the system, because the interfaces remained the same. Bugs and errors were vastly reduced during development because of this approach, allowing rapid development of the product with confidence that the protocol was operating as expected.

## Future Work

### IoT Security

Version 3 of the authentication protocol provides mutual challenge-response authentication with an implicit session key exchange providing an authenticated encrypted communication channel, relying upon 2 pre-shared keys. It achieves this using an incredibly limited set of resources on the client side (40KB program space, 2KB RAM). 

It is proposed that this protocol could be adapted to serve as a general purpose security protocol, providing authentication and encryption over HTTP in a similar fashion to TLS. Consider the protocol below, which is an extension of SAPv3 (stage 1 not shown).

![IoT Secure Authenticated Channel](http://i.imgur.com/pO0geYh.png)

Once mutual authentication and derivation of the session key has occured successfully, a secure channel is available for communicating over by incrementing the nonce by 1 per message.

Imagine a smart home system which may feature many individual “smart” devices that need to communicate with a central management device that could be accessed over HTTP. These smart devices would be the “clients” and the central management device would be the “server”. If they were preconfigured with the pre-shared keys, just like the biometric device, this would be a way to secure the communications and prevent attackers from wreaking havoc on the victim’s house, such as manipulating thermostat reports to overheat the entire house.

## References

FFIEC, 2005. *Authentication in an Internet Banking Environment.* [Online] 
Available at: http://www.ffiec.gov/pdf/authentication_guidance.pdf
[Accessed 13 October 2015].

Florencio, D. & Herley, C., 2007. *A large-scale study of web password habits.* New York, ACM, pp. 657-666.

NIST, 2013. *Electronic Authentication Guideline*, Gaithersburg: National Institute of Standards and Technology.

Verizon, 2015. *2015 Data Breach Investigation Report*, New York: Verizon.

## Bibliography

Bersani, F. & Tschofenig, H., 2007. *RFC 4764 - The EAP-PSK Protocol: A Pre-Shared Key Extensible Authentication Protocol (EAP) Method.* [Online] 
Available at: https://tools.ietf.org/html/rfc4764
[Accessed 8 January 2016].

CESG, 2012. *Requirements for Secure Delivery of Online Public Services – Annex B.* [Online] 
Available at: https://www.gov.uk/government/publications/requirements-for-secure-delivery-of-online-public-services
[Accessed 3 November 2015].

CESG, 2014. *Authentication credentials for online government services.* [Online] 
Available at: https://www.gov.uk/government/publications/authentication-credentials-for-online-government-services
[Accessed 4 November 2015].

Ferguson, N., Schneier, B. & Kohno, T., 2010. *Cryptography Engineering: Design Principles and Practical Applications.* 1st ed. Indianapolis: Wiley Publishing, Inc.

Fielding, R. & Reschke, J., 2014. *RFC 7230: Hypertext Transfer Protocol (HTTP/1.1): Message Syntax and Routing.* [Online] 
Available at: https://tools.ietf.org/html/rfc7230
[Accessed 10 February 2016].

Fielding, R. T., 2000. *Architectural Styles and the Design of Network-based Software Architectures*, Irvine: University of California.

Fowler, M., 2002. *Patterns of Enterprise Application Architecture.* 12th ed. Boston: Addison-Wesley.

Gong, L., 1993. *Variations on the themes of message freshness and replay.* Franconia, IEEE.

INCITS, 2007. *Study Report on Biometrics in E-Authentication*, Washington: InterNational Committee for Information Technology Standards.

Krawczyk, H. & Eronen, P., 2010. *RFC 5869: HMAC-based Extract-and-Expand Key Derivation Function (HKDF).* [Online] 
Available at: https://tools.ietf.org/html/rfc5869
[Accessed 15 March 2016].

Nir, Y. & Langley, A., 2015. *RFC 7539: ChaCha20 and Poly1305 for IETF Protocols.* [Online] 
Available at: https://tools.ietf.org/html/rfc7539
[Accessed 3 February 2016].

NIST, 2008. *FIPS 198-1: The Keyed-Hash Message Authentication Code (HMAC)*, Gaithersburg: National Institute of Standards and Technology.

NIST, 2011. *Recommendation for Key Derivation through Extraction-then-Expansion*, Gaithersburg: National Institute of Standards and Technology.

NIST, 2013. *Security and Privacy Controls for Federal Information Systems and Organizations*, Gaithersburg: National Institute of Standards and Technology.

